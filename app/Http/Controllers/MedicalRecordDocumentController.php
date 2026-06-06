<?php

namespace App\Http\Controllers;

use App\Events\MedicalDocumentShared;
use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Services\FileStorageManager;
use App\Services\MedicalRecordService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MedicalRecordDocumentController extends Controller
{
    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
        private readonly FileStorageManager $fileStorageManager,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $patient = $request->user()?->patient;

        if (! $patient) {
            abort(403, 'Perfil de paciente não encontrado.');
        }

        return $this->handleUpload($request, $patient);
    }

    public function storeForPatient(Request $request, Patient $patient): RedirectResponse
    {
        return $this->handleUpload($request, $patient);
    }

    protected function handleUpload(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('uploadDocument', $patient);

        $user = $request->user();

        $allowedVisibility = [
            MedicalDocument::VISIBILITY_PATIENT,
            MedicalDocument::VISIBILITY_SHARED,
        ];

        if ($user?->isDoctor()) {
            $allowedVisibility[] = MedicalDocument::VISIBILITY_DOCTOR;
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:'.config('telemedicine.uploads.medical_document_max_kb', 10240)],
            'category' => ['required', Rule::in([
                MedicalDocument::CATEGORY_EXAM,
                MedicalDocument::CATEGORY_PRESCRIPTION,
                MedicalDocument::CATEGORY_REPORT,
                MedicalDocument::CATEGORY_OTHER,
            ])],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'appointment_id' => ['nullable', Rule::exists('appointments', 'id')->where('patient_id', $patient->id)],
            'visibility' => ['nullable', Rule::in($allowedVisibility)],
        ]);

        $file = $request->file('file');
        $domain = $this->resolveDomainByCategory($validated['category']);
        $diskName = $this->fileStorageManager->diskName($domain);
        $path = $file->store(
            $this->fileStorageManager->buildPath($domain, (string) $patient->id),
            $diskName
        );

        $document = MedicalDocument::create([
            'patient_id' => $patient->id,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'doctor_id' => $user?->isDoctor() ? $user->doctor?->id : null,
            'uploaded_by' => $user?->id,
            'category' => $validated['category'],
            'name' => $validated['name'] ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'description' => $validated['description'] ?? null,
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'storage_domain' => $domain,
            ],
            'visibility' => $validated['visibility'] ?? MedicalDocument::VISIBILITY_SHARED,
        ]);

        $this->medicalRecordService->logAccess(
            $user,
            $patient,
            'upload',
            ['document_id' => $document->id]
        );

        if ($document->appointment_id && $document->visibility !== MedicalDocument::VISIBILITY_DOCTOR) {
            MedicalDocumentShared::dispatch($document);
        }

        return back()->with('status', 'Documento enviado com sucesso.');
    }

    public function download(Request $request, MedicalDocument $document): StreamedResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $document->loadMissing('patient');

        if ($user->isPatient()) {
            if ($document->patient_id !== $user->patient?->id) {
                abort(403);
            }

            if ($document->visibility === MedicalDocument::VISIBILITY_DOCTOR) {
                abort(403);
            }
        } elseif ($user->isDoctor()) {
            $this->authorize('downloadDocument', $document->patient);

            if ($document->visibility === MedicalDocument::VISIBILITY_PATIENT) {
                abort(403);
            }
        } else {
            abort(403);
        }

        $domain = $this->resolveDomainByDocument($document);
        $disk = $this->fileStorageManager->disk($domain);

        if (! $disk->exists($document->file_path) && ! $this->ensureDemoDocumentExists($disk, $document)) {
            abort(404);
        }

        $inline = $request->query('disposition') === 'inline' && $this->isInlineSafeMime($document->file_type);

        $this->medicalRecordService->logAccess(
            $user,
            $document->patient,
            'download',
            ['document_id' => $document->id, 'disposition' => $inline ? 'inline' : 'attachment']
        );

        if ($inline) {
            return $disk->response($document->file_path, $this->resolveDownloadFilename($document), [
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return $disk->download($document->file_path, $this->resolveDownloadFilename($document));
    }

    private function isInlineSafeMime(?string $mime): bool
    {
        // HTML/SVG inline executariam script no browser — só PDF e imagens raster são seguros
        return in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true);
    }

    public function downloadForPatient(Request $request, Patient $patient, MedicalDocument $document): StreamedResponse
    {
        if ($document->patient_id !== $patient->id) {
            abort(404);
        }

        return $this->download($request, $document);
    }

    private function resolveDomainByCategory(string $category): string
    {
        return match ($category) {
            MedicalDocument::CATEGORY_PRESCRIPTION => FileStorageManager::DOMAIN_PRESCRIPTIONS,
            default => FileStorageManager::DOMAIN_MEDICAL_DOCUMENTS,
        };
    }

    private function resolveDomainByDocument(MedicalDocument $document): string
    {
        $metadataDomain = data_get($document->metadata, 'storage_domain');

        if (is_string($metadataDomain) && $metadataDomain !== '') {
            return $metadataDomain;
        }

        return $this->resolveDomainByCategory($document->category);
    }

    private function ensureDemoDocumentExists(FilesystemAdapter $disk, MedicalDocument $document): bool
    {
        if (app()->isProduction()) {
            return false;
        }

        $reference = data_get($document->metadata, 'reference');
        if (! is_string($reference) || ! str_starts_with($reference, 'DOC-DEMO-')) {
            return false;
        }

        $pdfContent = app('dompdf.wrapper')
            ->loadHTML(
                '<h1>Laudo Hemograma (Demo)</h1><p>Arquivo gerado automaticamente para ambiente local de desenvolvimento.</p>'
            )
            ->output();

        $disk->put($document->file_path, $pdfContent);

        return $disk->exists($document->file_path);
    }

    private function resolveDownloadFilename(MedicalDocument $document): string
    {
        $fallbackName = trim($document->name) !== '' ? trim($document->name) : 'documento';
        $originalName = data_get($document->metadata, 'original_name');
        $baseName = is_string($originalName) && trim($originalName) !== '' ? trim($originalName) : $fallbackName;

        if (Str::contains($baseName, '.')) {
            return $baseName;
        }

        $extension = match ($document->file_type) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => pathinfo($document->file_path, PATHINFO_EXTENSION) ?: 'bin',
        };

        return "{$baseName}.{$extension}";
    }
}
