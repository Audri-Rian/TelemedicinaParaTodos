<?php

namespace App\Services;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\MedicalDocument;
use App\Models\MedicalRecordAuditLog;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\VitalSign;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MedicalRecordService
{
    /**
     * Retorna o payload completo para o prontuário do paciente.
     */
    public function getPatientMedicalRecord(Patient $patient, array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $appointments = $this->getAppointmentsForRecord($patient, $normalizedFilters);

        return [
            'patient' => $this->formatPatient($patient),
            'timeline' => $appointments,
            'consultations' => $appointments,
            'prescriptions' => $this->getPrescriptionsForRecord($patient, $normalizedFilters),
            'examinations' => $this->getExaminationsForRecord($patient, $normalizedFilters),
            'documents' => $this->getDocumentsForRecord($patient, $normalizedFilters),
            'vital_signs' => $this->getVitalSignsForRecord($patient, $normalizedFilters),
            'upcoming_appointments' => $this->getUpcomingAppointments($patient),
            'metrics' => $this->buildMetrics($patient),
            'filters' => $normalizedFilters,
        ];
    }

    /**
     * Busca consultas com filtros.
     */
    public function getAppointmentsForRecord(Patient $patient, array $filters = []): Collection
    {
        $query = Appointments::with([
            'doctor.user',
            'doctor.specializations',
            'medicalDocuments',
            'prescriptions',
            'examinations',
            'vitalSigns',
        ])->where('patient_id', $patient->id);

        $this->applyAppointmentFilters($query, $filters);

        return $query
            ->orderByDesc('scheduled_at')
            ->get()
            ->map(fn (Appointments $appointment) => $this->formatAppointment($appointment));
    }

    /**
     * Busca prescrições do paciente.
     */
    public function getPrescriptionsForRecord(Patient $patient, array $filters = []): Collection
    {
        $query = Prescription::with(['doctor.user'])
            ->where('patient_id', $patient->id);

        if (!empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('instructions', 'like', "%{$search}%")
                    ->orWhere('medications->0->name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['prescription_status'])) {
            $query->whereIn('status', (array) $filters['prescription_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('issued_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('issued_at', '<=', $filters['date_to']);
        }

        return $query
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Prescription $prescription) => $this->formatPrescription($prescription));
    }

    /**
     * Busca exames do paciente.
     */
    public function getExaminationsForRecord(Patient $patient, array $filters = []): Collection
    {
        $query = Examination::with(['doctor.user', 'appointment'])
            ->where('patient_id', $patient->id);

        if (!empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['examination_type'])) {
            $query->where('type', $filters['examination_type']);
        }

        if (!empty($filters['examination_status'])) {
            $query->whereIn('status', (array) $filters['examination_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('requested_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('requested_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('results->summary', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('requested_at')
            ->get()
            ->map(fn (Examination $examination) => $this->formatExamination($examination));
    }

    /**
     * Busca documentos médicos do paciente.
     */
    public function getDocumentsForRecord(Patient $patient, array $filters = []): Collection
    {
        $query = MedicalDocument::with(['doctor.user', 'uploader'])
            ->where('patient_id', $patient->id);

        if (!empty($filters['document_category'])) {
            $query->where('category', $filters['document_category']);
        }

        if (!empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (MedicalDocument $document) => $this->formatDocument($document));
    }

    /**
     * Busca sinais vitais registrados.
     */
    public function getVitalSignsForRecord(Patient $patient, array $filters = []): Collection
    {
        $query = VitalSign::with(['doctor.user'])
            ->where('patient_id', $patient->id);

        if (!empty($filters['date_from'])) {
            $query->whereDate('recorded_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('recorded_at', '<=', $filters['date_to']);
        }

        return $query
            ->orderByDesc('recorded_at')
            ->limit($filters['vitals_limit'] ?? 50)
            ->get()
            ->map(fn (VitalSign $vital) => $this->formatVitalSign($vital));
    }

    /**
     * Retorna consultas futuras.
     */
    public function getUpcomingAppointments(Patient $patient): Collection
    {
        return Appointments::with(['doctor.user', 'doctor.specializations'])
            ->where('patient_id', $patient->id)
            ->whereIn('status', [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->take(10)
            ->get()
            ->map(fn (Appointments $appointment) => $this->formatAppointment($appointment));
    }

    /**
     * Prepara dados para exportação em PDF.
     */
    public function prepareDataForExport(Patient $patient, array $filters = []): array
    {
        $record = $this->getPatientMedicalRecord($patient, $filters);
        $record['generated_at'] = now()->toIso8601String();

        return $record;
    }

    /**
     * Verifica se o médico tem permissão para visualizar o prontuário.
     */
    public function canDoctorViewPatientRecord(Doctor $doctor, Patient $patient): bool
    {
        return Appointments::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->whereIn('status', [
                Appointments::STATUS_COMPLETED,
                Appointments::STATUS_IN_PROGRESS,
                Appointments::STATUS_SCHEDULED,
            ])
            ->exists();
    }

    /**
     * Registra auditoria de acesso.
     */
    public function logAccess(User $user, Patient $patient, string $action, array $metadata = []): MedicalRecordAuditLog
    {
        return MedicalRecordAuditLog::create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Normaliza filtros recebidos do controller.
     */
    public function normalizeFilters(array $filters): array
    {
        $dateKeys = ['date_from', 'date_to'];

        foreach ($dateKeys as $key) {
            if (!empty($filters[$key])) {
                $filters[$key] = Carbon::parse($filters[$key])->startOfDay();
            }
        }

        return $filters;
    }

    /**
     * Aplica filtros de consulta.
     */
    protected function applyAppointmentFilters(Builder $query, array $filters): void
    {
        $statuses = $filters['appointment_status'] ?? [Appointments::STATUS_COMPLETED];

        if (!empty($statuses)) {
            $query->whereIn('status', (array) $statuses);
        }

        if (!empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('scheduled_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('scheduled_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('notes', 'like', "%{$search}%")
                    ->orWhere('metadata->diagnosis', 'like', "%{$search}%")
                    ->orWhereHas('doctor.user', function (Builder $doctorQuery) use ($search) {
                        $doctorQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }
    }

    /**
     * Monta dados do paciente.
     */
    protected function formatPatient(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'user' => [
                'name' => $patient->user->name,
                'avatar' => $patient->user->getAvatarUrl(),
            ],
            'date_of_birth' => $patient->date_of_birth?->toIso8601String(),
            'gender' => $patient->gender,
            'age' => $patient->age,
            'blood_type' => $patient->blood_type,
            'medical_history' => $patient->medical_history,
            'allergies' => $patient->allergies,
            'current_medications' => $patient->current_medications,
            'height' => $patient->height,
            'weight' => $patient->weight,
            'bmi' => $patient->bmi,
            'bmi_category' => $patient->bmi_category,
            'insurance_provider' => $patient->insurance_provider,
            'insurance_number' => $patient->insurance_number,
        ];
    }

    protected function formatAppointment(Appointments $appointment): array
    {
        return [
            'id' => $appointment->id,
            'scheduled_at' => $appointment->scheduled_at?->toIso8601String(),
            'started_at' => $appointment->started_at?->toIso8601String(),
            'ended_at' => $appointment->ended_at?->toIso8601String(),
            'status' => $appointment->status,
            'notes' => $appointment->notes,
            'doctor' => [
                'id' => $appointment->doctor->id,
                'user' => [
                    'name' => $appointment->doctor->user->name,
                    'avatar' => $appointment->doctor->user->getAvatarUrl(),
                ],
                'specializations' => $appointment->doctor->specializations->map(fn ($spec) => [
                    'id' => $spec->id,
                    'name' => $spec->name,
                ]),
            ],
            'metadata' => $appointment->metadata ?? [],
            'diagnosis' => $appointment->metadata['diagnosis'] ?? null,
            'cid10' => $appointment->metadata['cid10'] ?? null,
            'symptoms' => $appointment->metadata['symptoms'] ?? null,
            'requested_exams' => $appointment->metadata['requested_exams'] ?? null,
            'instructions' => $appointment->metadata['instructions'] ?? null,
            'attachments' => $appointment->metadata['attachments'] ?? [],
            'prescriptions' => $appointment->prescriptions->map(fn (Prescription $prescription) => $this->formatPrescription($prescription)),
            'examinations' => $appointment->examinations->map(fn (Examination $examination) => $this->formatExamination($examination)),
            'documents' => $appointment->medicalDocuments->map(fn (MedicalDocument $document) => $this->formatDocument($document)),
            'vital_signs' => $appointment->vitalSigns->map(fn (VitalSign $vital) => $this->formatVitalSign($vital)),
        ];
    }

    protected function formatPrescription(Prescription $prescription): array
    {
        return [
            'id' => $prescription->id,
            'doctor' => [
                'id' => $prescription->doctor->id,
                'name' => $prescription->doctor->user->name,
            ],
            'medications' => $prescription->medications,
            'instructions' => $prescription->instructions,
            'valid_until' => $prescription->valid_until?->toDateString(),
            'status' => $prescription->status,
            'metadata' => $prescription->metadata,
            'issued_at' => $prescription->issued_at?->toIso8601String(),
        ];
    }

    protected function formatExamination(Examination $examination): array
    {
        return [
            'id' => $examination->id,
            'name' => $examination->name,
            'type' => $examination->type,
            'doctor' => $examination->doctor ? [
                'id' => $examination->doctor->id,
                'name' => $examination->doctor->user->name,
            ] : null,
            'status' => $examination->status,
            'requested_at' => $examination->requested_at?->toIso8601String(),
            'completed_at' => $examination->completed_at?->toIso8601String(),
            'results' => $examination->results,
            'attachment_url' => $examination->attachment_url,
            'metadata' => $examination->metadata,
        ];
    }

    protected function formatDocument(MedicalDocument $document): array
    {
        return [
            'id' => $document->id,
            'name' => $document->name,
            'category' => $document->category,
            'file_path' => $document->file_path,
            'file_type' => $document->file_type,
            'file_size' => $document->file_size,
            'description' => $document->description,
            'visibility' => $document->visibility,
            'uploaded_at' => $document->created_at?->toIso8601String(),
            'doctor' => $document->doctor ? [
                'id' => $document->doctor->id,
                'name' => $document->doctor->user->name,
            ] : null,
            'uploaded_by' => $document->uploader ? [
                'id' => $document->uploader->id,
                'name' => $document->uploader->name,
            ] : null,
            'metadata' => $document->metadata,
        ];
    }

    protected function formatVitalSign(VitalSign $vital): array
    {
        return [
            'id' => $vital->id,
            'recorded_at' => $vital->recorded_at?->toIso8601String(),
            'doctor' => $vital->doctor ? [
                'id' => $vital->doctor->id,
                'name' => $vital->doctor->user->name,
            ] : null,
            'blood_pressure' => [
                'systolic' => $vital->blood_pressure_systolic,
                'diastolic' => $vital->blood_pressure_diastolic,
            ],
            'temperature' => $vital->temperature,
            'heart_rate' => $vital->heart_rate,
            'respiratory_rate' => $vital->respiratory_rate,
            'oxygen_saturation' => $vital->oxygen_saturation,
            'weight' => $vital->weight,
            'height' => $vital->height,
            'notes' => $vital->notes,
            'metadata' => $vital->metadata,
        ];
    }

    protected function buildMetrics(Patient $patient): array
    {
        $lastAppointment = $patient->appointments()
            ->where('status', Appointments::STATUS_COMPLETED)
            ->latest('scheduled_at')
            ->first();

        return [
            'total_consultations' => $patient->appointments()->where('status', Appointments::STATUS_COMPLETED)->count(),
            'total_prescriptions' => $patient->prescriptions()->count(),
            'total_examinations' => $patient->examinations()->count(),
            'last_consultation_at' => $lastAppointment?->scheduled_at?->toIso8601String(),
        ];
    }

    public function generatePdfDocument(
        Patient $patient,
        User $requester,
        array $filters = [],
        string $visibility = MedicalDocument::VISIBILITY_PATIENT,
    ): array {
        $payload = $this->prepareDataForExport($patient, $filters);
        $pdf = Pdf::loadView('pdf.medical-record', $payload)->setPaper('a4');

        $timestamp = now();
        $filename = sprintf('medical-record-%s.pdf', $timestamp->format('YmdHis'));
        $path = "medical-records/exports/{$patient->id}/{$filename}";

        $disk = Storage::disk('public');
        $disk->put($path, $pdf->output());
        $fileSize = $disk->size($path);

        MedicalDocument::create([
            'patient_id' => $patient->id,
            'doctor_id' => $requester->doctor?->id,
            'uploaded_by' => $requester->id,
            'appointment_id' => null,
            'category' => MedicalDocument::CATEGORY_REPORT,
            'name' => sprintf('Exportação do Prontuário - %s', $timestamp->format('d/m/Y H:i')),
            'file_path' => $path,
            'file_type' => 'application/pdf',
            'file_size' => $fileSize,
            'description' => 'Exportação completa do prontuário em PDF',
            'metadata' => [
                'filters' => $filters,
                'generated_by' => $requester->id,
            ],
            'visibility' => $visibility,
        ]);

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }
}

