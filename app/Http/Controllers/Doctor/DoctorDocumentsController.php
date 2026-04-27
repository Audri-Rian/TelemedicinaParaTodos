<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\DoctorDocumentsIndexRequest;
use App\Models\Appointments;
use App\Models\MedicalDocument;
use App\Models\Patient;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoctorDocumentsController extends Controller
{
    public function index(Request $request): Response
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            abort(403, 'Apenas médicos podem acessar esta página.');
        }

        $patientIds = Appointments::query()
            ->where('doctor_id', $doctor->id)
            ->distinct()
            ->pluck('patient_id');

        $patients = Patient::query()
            ->select(['id', 'user_id', 'cpf', 'date_of_birth', 'gender'])
            ->with('user:id,name')
            ->whereIn('id', $patientIds)
            ->get()
            ->map(fn (Patient $p) => [
                'id' => $p->id,
                'name' => $p->user->name ?? '',
                'cpf' => $this->safePatientCpf($p),
                'age' => $p->age,
                'sex' => $this->sexLabel($p->gender),
            ])
            ->values()
            ->all();

        return Inertia::render('Doctor/Documents', [
            'patients' => $patients,
        ]);
    }

    public function history(DoctorDocumentsIndexRequest $request): Response
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            abort(403, 'Apenas médicos podem acessar esta página.');
        }

        $validated = $request->validated();
        $patientIds = Appointments::query()
            ->where('doctor_id', $doctor->id)
            ->distinct()
            ->pluck('patient_id');

        $patients = Patient::query()
            ->select(['id', 'user_id', 'cpf', 'date_of_birth', 'gender'])
            ->with('user:id,name')
            ->whereIn('id', $patientIds)
            ->get()
            ->map(fn (Patient $p) => [
                'id' => $p->id,
                'name' => $p->user->name ?? '',
            ])
            ->values()
            ->all();

        $documents = MedicalDocument::query()
            ->with(['patient.user'])
            ->where('doctor_id', $doctor->id)
            ->when(
                ! empty($validated['patient_id']),
                fn (Builder $query) => $query->where('patient_id', $validated['patient_id'])
            )
            ->when(
                ! empty($validated['category']),
                fn (Builder $query) => $query->where('category', $validated['category'])
            )
            ->when(
                ! empty($validated['period_days']),
                fn (Builder $query) => $query->where('created_at', '>=', now()->subDays((int) $validated['period_days']))
            )
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn (MedicalDocument $document) => [
                'id' => $document->id,
                'name' => $document->name,
                'category' => $document->category,
                'categoryLabel' => $this->categoryLabel($document->category),
                'patient' => [
                    'id' => $document->patient?->id,
                    'name' => $document->patient?->user?->name ?? 'Paciente',
                ],
                'uploadedAt' => $document->created_at?->toISOString(),
                'visibility' => $document->visibility,
                'fileUrl' => URL::temporarySignedRoute(
                    'doctor.documents.download',
                    now()->addMinutes(15),
                    ['document' => $document->id],
                ),
            ])
            ->values()
            ->all();

        return Inertia::render('Doctor/DocumentsHistory', [
            'patients' => $patients,
            'documents' => $documents,
            'filters' => [
                'patient_id' => $validated['patient_id'] ?? null,
                'category' => $validated['category'] ?? null,
                'period_days' => $validated['period_days'] ?? 30,
            ],
        ]);
    }

    public function download(MedicalDocument $document): StreamedResponse
    {
        $doctor = request()->user()?->doctor;

        if (! $doctor || $document->doctor_id !== $doctor->id) {
            abort(403);
        }

        if ($document->visibility === MedicalDocument::VISIBILITY_PATIENT) {
            abort(403);
        }

        $disk = \Storage::disk('local')->exists($document->file_path) ? 'local' : 'public';

        return \Storage::disk($disk)->download($document->file_path, $document->name);
    }

    private function sexLabel(?string $gender): ?string
    {
        return match (strtolower((string) $gender)) {
            Patient::GENDER_MALE => 'M',
            Patient::GENDER_FEMALE => 'F',
            default => null,
        };
    }

    private function safePatientCpf(Patient $patient): ?string
    {
        try {
            return $patient->cpf;
        } catch (DecryptException) {
            Log::warning('Paciente com CPF inválido para decrypt no módulo de documentos', [
                'patient_id' => $patient->id,
            ]);

            $rawCpf = $patient->getRawOriginal('cpf');

            if (! is_string($rawCpf)) {
                return null;
            }

            $digitsOnly = preg_replace('/\D/', '', $rawCpf);

            return strlen((string) $digitsOnly) === 11 ? $digitsOnly : null;
        }
    }

    private function categoryLabel(?string $category): string
    {
        return match ($category) {
            MedicalDocument::CATEGORY_EXAM => 'Exame',
            MedicalDocument::CATEGORY_PRESCRIPTION => 'Prescrição',
            MedicalDocument::CATEGORY_REPORT => 'Relatório',
            MedicalDocument::CATEGORY_OTHER => 'Outro',
            default => 'Documento',
        };
    }
}
