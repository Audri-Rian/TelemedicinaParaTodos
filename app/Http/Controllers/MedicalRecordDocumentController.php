<?php

namespace App\Http\Controllers;

use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Services\MedicalRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MedicalRecordDocumentController extends Controller
{
    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
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

        $allowedVisibility = [
            MedicalDocument::VISIBILITY_PATIENT,
            MedicalDocument::VISIBILITY_SHARED,
        ];

        if ($request->user()?->isDoctor()) {
            $allowedVisibility[] = MedicalDocument::VISIBILITY_DOCTOR;
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('telemedicine.uploads.medical_document_max_kb', 10240)],
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
        $medicalRecordsDisk = config('telemedicine.medical_records.disk');
        $path = $file->store("medical-records/uploads/{$patient->id}", $medicalRecordsDisk);

        $document = MedicalDocument::create([
            'patient_id' => $patient->id,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'doctor_id' => $request->user()?->doctor?->id,
            'uploaded_by' => $request->user()?->id,
            'category' => $validated['category'],
            'name' => $validated['name'] ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'description' => $validated['description'] ?? null,
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
            ],
            'visibility' => $validated['visibility'] ?? MedicalDocument::VISIBILITY_SHARED,
        ]);

        $this->medicalRecordService->logAccess(
            $request->user(),
            $patient,
            'upload',
            ['document_id' => $document->id]
        );

        return back()->with('status', 'Documento enviado com sucesso.');
    }

    public function download(Request $request, MedicalDocument $document): StreamedResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isPatient()) {
            if ($document->patient_id !== $user->patient?->id) {
                abort(403);
            }

            if ($document->visibility === MedicalDocument::VISIBILITY_DOCTOR) {
                abort(403);
            }
        } elseif ($user->isDoctor()) {
            if ($document->doctor_id !== $user->doctor?->id) {
                abort(403);
            }

            if ($document->visibility === MedicalDocument::VISIBILITY_PATIENT) {
                abort(403);
            }
        } else {
            abort(403);
        }

        $medicalRecordsDisk = config('telemedicine.medical_records.disk');

        if (! Storage::disk($medicalRecordsDisk)->exists($document->file_path)) {
            abort(404);
        }

        $this->medicalRecordService->logAccess(
            $user,
            $document->patient,
            'download',
            ['document_id' => $document->id]
        );

        return Storage::disk($medicalRecordsDisk)->download($document->file_path, $document->name);
    }
}
