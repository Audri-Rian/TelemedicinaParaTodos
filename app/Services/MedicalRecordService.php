<?php

namespace App\Services;

use App\Models\Appointments;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\MedicalDocument;
use App\Models\MedicalRecordAuditLog;
use App\Models\MedicalCertificate;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\VitalSign;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'diagnoses' => $this->getDiagnosesForRecord($patient, $normalizedFilters),
            'clinical_notes' => $this->getClinicalNotesForRecord($patient, $normalizedFilters),
            'medical_certificates' => $this->getMedicalCertificatesForRecord($patient, $normalizedFilters),
            'upcoming_appointments' => $this->getUpcomingAppointments($patient),
            'metrics' => $this->buildMetrics($patient),
            'filters' => $normalizedFilters,
        ];
    }

    /**
     * Lista pacientes atendidos pelo médico com indicadores agregados.
     */
    public function getDoctorPatientList(Doctor $doctor, array $filters = []): Collection
    {
        $query = Appointments::query()
            ->with([
                'patient.user',
                'patient.medicalDocuments',
            ])
            ->where('doctor_id', $doctor->id);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('patient.user', function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('scheduled_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('scheduled_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['diagnosis'])) {
            $diagnosis = $filters['diagnosis'];
            $query->where('metadata->diagnosis', 'like', "%{$diagnosis}%");
        }

        $appointments = $query
            ->orderByDesc('scheduled_at')
            ->get();

        return $appointments
            ->groupBy('patient_id')
            ->map(function (Collection $patientAppointments) {
                /** @var Appointments $latest */
                $latest = $patientAppointments->sortByDesc('scheduled_at')->first();
                $patient = $latest->patient;

                $pendingExams = $patientAppointments
                    ->flatMap(fn (Appointments $appt) => $appt->examinations)
                    ->whereIn('status', [Examination::STATUS_REQUESTED, Examination::STATUS_IN_PROGRESS])
                    ->count();

                return [
                    'patient' => $this->formatPatient($patient),
                    'last_consultation_at' => $latest->scheduled_at?->toIso8601String(),
                    'consultations_count' => $patientAppointments->count(),
                    'last_diagnosis' => $latest->metadata['diagnosis'] ?? null,
                    'alerts' => [
                        'pending_exams' => $pendingExams,
                        'next_appointment' => $patient->appointments()
                            ->where('scheduled_at', '>', now())
                            ->orderBy('scheduled_at')
                            ->first()?->scheduled_at?->toIso8601String(),
                    ],
                ];
            })
            ->values();
    }

    /**
     * Retorna o prontuário com filtros aplicados ao contexto do médico.
     */
    public function getDoctorPatientMedicalRecord(Doctor $doctor, Patient $patient, array $filters = []): array
    {
        $record = $this->getPatientMedicalRecord($patient, $filters);

        $filterByDoctor = static fn (Collection $items, ?string $doctorPath = 'doctor.id'): Collection => $items
            ->filter(static fn ($item) => data_get($item, $doctorPath) === $doctor->id)
            ->values();

        $record['timeline'] = $filterByDoctor($record['timeline']);
        $record['consultations'] = $record['timeline'];
        $record['prescriptions'] = $filterByDoctor($record['prescriptions']);
        $record['examinations'] = $filterByDoctor($record['examinations']);
        $record['documents'] = collect($record['documents'])
            ->filter(function ($document) use ($doctor) {
                $visibility = $document['visibility'] ?? MedicalDocument::VISIBILITY_SHARED;
                return $visibility !== MedicalDocument::VISIBILITY_PATIENT
                    || ($document['doctor']['id'] ?? null) === $doctor->id;
            })
            ->values();
        $record['vital_signs'] = $filterByDoctor($record['vital_signs']);
        $record['diagnoses'] = $this->getDiagnosesForRecord($patient, $filters, $doctor);
        $record['clinical_notes'] = $this->getClinicalNotesForRecord($patient, $filters, $doctor);
        $record['medical_certificates'] = $this->getMedicalCertificatesForRecord($patient, $filters, $doctor);

        return $record;
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
            ->limit($filters['vitals_limit'] ?? config('telemedicine.medical_records.vitals_limit', 50))
            ->get()
            ->map(fn (VitalSign $vital) => $this->formatVitalSign($vital));
    }

    /**
     * Busca diagnósticos registrados para o paciente.
     */
    public function getDiagnosesForRecord(Patient $patient, array $filters = [], ?Doctor $doctor = null): Collection
    {
        $query = Diagnosis::with(['doctor.user', 'appointment'])
            ->where('patient_id', $patient->id);

        if ($doctor) {
            $query->where('doctor_id', $doctor->id);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('cid10_code', 'like', "%{$search}%")
                    ->orWhere('cid10_description', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Diagnosis $diagnosis) => $this->formatDiagnosis($diagnosis));
    }

    /**
     * Busca notas clínicas.
     */
    public function getClinicalNotesForRecord(Patient $patient, array $filters = [], ?Doctor $doctor = null): Collection
    {
        $query = ClinicalNote::with(['doctor.user', 'appointment'])
            ->where('patient_id', $patient->id);

        if (!$doctor) {
            $query->where('is_private', false);
        }

        if ($doctor) {
            $query->where(function (Builder $builder) use ($doctor) {
                $builder->where('is_private', false)
                    ->orWhere('doctor_id', $doctor->id);
            });
        }

        if (!empty($filters['note_category'])) {
            $query->where('category', $filters['note_category']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhereJsonContains('tags', $search);
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ClinicalNote $note) => $this->formatClinicalNote($note));
    }

    /**
     * Busca atestados emitidos.
     */
    public function getMedicalCertificatesForRecord(Patient $patient, array $filters = [], ?Doctor $doctor = null): Collection
    {
        $query = MedicalCertificate::with(['doctor.user', 'appointment'])
            ->where('patient_id', $patient->id);

        if ($doctor) {
            $query->where('doctor_id', $doctor->id);
        }

        if (!empty($filters['certificate_status'])) {
            $query->whereIn('status', (array) $filters['certificate_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('start_date', '<=', $filters['date_to']);
        }

        return $query
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (MedicalCertificate $certificate) => $this->formatMedicalCertificate($certificate));
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
            ->take(config('telemedicine.medical_records.search_limit', 10))
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

    protected function formatDiagnosis(Diagnosis $diagnosis): array
    {
        return [
            'id' => $diagnosis->id,
            'appointment_id' => $diagnosis->appointment_id,
            'cid10_code' => $diagnosis->cid10_code,
            'cid10_description' => $diagnosis->cid10_description,
            'type' => $diagnosis->diagnosis_type,
            'description' => $diagnosis->description,
            'doctor' => [
                'id' => $diagnosis->doctor->id,
                'name' => $diagnosis->doctor->user->name,
            ],
            'created_at' => $diagnosis->created_at?->toIso8601String(),
        ];
    }

    protected function formatClinicalNote(ClinicalNote $note): array
    {
        return [
            'id' => $note->id,
            'appointment_id' => $note->appointment_id,
            'title' => $note->title,
            'content' => $note->content,
            'is_private' => $note->is_private,
            'category' => $note->category,
            'tags' => $note->tags,
            'version' => $note->version,
            'doctor' => [
                'id' => $note->doctor->id,
                'name' => $note->doctor->user->name,
            ],
            'created_at' => $note->created_at?->toIso8601String(),
        ];
    }

    protected function formatMedicalCertificate(MedicalCertificate $certificate): array
    {
        return [
            'id' => $certificate->id,
            'appointment_id' => $certificate->appointment_id,
            'type' => $certificate->type,
            'start_date' => $certificate->start_date?->toDateString(),
            'end_date' => $certificate->end_date?->toDateString(),
            'days' => $certificate->days,
            'reason' => $certificate->reason,
            'restrictions' => $certificate->restrictions,
            'status' => $certificate->status,
            'verification_code' => $certificate->verification_code,
            'pdf_url' => $certificate->pdf_url,
            'doctor' => [
                'id' => $certificate->doctor->id,
                'name' => $certificate->doctor->user->name,
                'crm' => $certificate->doctor->crm,
            ],
            'created_at' => $certificate->created_at?->toIso8601String(),
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

    public function registerDiagnosis(Appointments $appointment, Doctor $doctor, array $payload): Diagnosis
    {
        return DB::transaction(function () use ($appointment, $doctor, $payload) {
            $diagnosis = Diagnosis::create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $doctor->id,
                'patient_id' => $appointment->patient_id,
                'cid10_code' => $payload['cid10_code'],
                'cid10_description' => $payload['cid10_description'] ?? null,
                'diagnosis_type' => $payload['type'] ?? Diagnosis::TYPE_PRINCIPAL,
                'description' => $payload['description'] ?? null,
            ]);

            $metadata = $appointment->metadata ?? [];
            $metadata['diagnosis'] = $diagnosis->description ?? $diagnosis->cid10_description;
            $metadata['cid10'] = $diagnosis->cid10_code;
            $metadata['cid10_description'] = $diagnosis->cid10_description;

            $appointment->update(['metadata' => $metadata]);

            $this->logAccess(
                $this->resolveDoctorUser($doctor),
                $appointment->patient,
                'diagnosis_registered',
                ['diagnosis_id' => $diagnosis->id],
            );

            return $diagnosis;
        });
    }

    public function issuePrescription(Doctor $doctor, Patient $patient, Appointments $appointment, array $payload): Prescription
    {
        if ($appointment->patient_id !== $patient->id) {
            throw new \RuntimeException('Paciente não está associado à consulta.');
        }

        $prescription = Prescription::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'medications' => $payload['medications'],
            'instructions' => $payload['instructions'] ?? null,
            'valid_until' => !empty($payload['valid_until'])
                ? Carbon::parse($payload['valid_until'])
                : now()->addDays(config('telemedicine.medical_records.prescription_default_validity_days', 30)),
            'status' => Prescription::STATUS_ACTIVE,
            'metadata' => $payload['metadata'] ?? null,
            'issued_at' => now(),
        ]);

        $this->logAccess(
            $this->resolveDoctorUser($doctor),
            $patient,
            'prescription_issued',
            ['prescription_id' => $prescription->id],
        );

        return $prescription;
    }

    public function requestExamination(Doctor $doctor, Patient $patient, Appointments $appointment, array $payload): Examination
    {
        $examination = Examination::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'type' => $payload['type'] ?? Examination::TYPE_LAB,
            'name' => $payload['name'],
            'requested_at' => now(),
            'status' => Examination::STATUS_REQUESTED,
            'metadata' => [
                'justification' => $payload['justification'] ?? null,
                'priority' => $payload['priority'] ?? 'normal',
                'instructions' => $payload['instructions'] ?? null,
            ],
        ]);

        $this->logAccess(
            $this->resolveDoctorUser($doctor),
            $patient,
            'examination_requested',
            ['examination_id' => $examination->id],
        );

        return $examination;
    }

    public function createClinicalNote(Doctor $doctor, Patient $patient, Appointments $appointment, array $payload): ClinicalNote
    {
        $version = 1;
        if (!empty($payload['parent_id'])) {
            $parent = ClinicalNote::findOrFail($payload['parent_id']);
            $version = ($parent->version ?? 1) + 1;
        }

        $note = ClinicalNote::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'title' => $payload['title'],
            'content' => $payload['content'],
            'is_private' => $payload['is_private'] ?? true,
            'category' => $payload['category'] ?? ClinicalNote::CATEGORY_GENERAL,
            'tags' => $payload['tags'] ?? [],
            'version' => $version,
            'parent_id' => $payload['parent_id'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
        ]);

        $this->logAccess(
            $this->resolveDoctorUser($doctor),
            $patient,
            'clinical_note_created',
            ['note_id' => $note->id],
        );

        return $note;
    }

    public function issueCertificate(Doctor $doctor, Patient $patient, Appointments $appointment, array $payload): MedicalCertificate
    {
        $verificationCode = $this->generateVerificationCode();

        $certificate = MedicalCertificate::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'type' => $payload['type'] ?? MedicalCertificate::TYPE_ATTENDANCE,
            'start_date' => Carbon::parse($payload['start_date']),
            'end_date' => !empty($payload['end_date']) ? Carbon::parse($payload['end_date']) : null,
            'days' => $payload['days'] ?? 1,
            'reason' => $payload['reason'],
            'restrictions' => $payload['restrictions'] ?? null,
            'signature_hash' => $payload['signature_hash'] ?? null,
            'crm_number' => $doctor->crm,
            'verification_code' => $verificationCode,
            'status' => MedicalCertificate::STATUS_ACTIVE,
            'metadata' => $payload['metadata'] ?? null,
        ]);

        $pdf = $this->buildMedicalCertificatePdf($certificate);
        if ($pdf) {
            $certificate->forceFill(['pdf_url' => $pdf['public_path']])->save();
        }

        $this->logAccess(
            $this->resolveDoctorUser($doctor),
            $patient,
            'medical_certificate_issued',
            ['certificate_id' => $certificate->id],
        );

        return $certificate;
    }

    public function registerVitalSigns(Appointments $appointment, Doctor $doctor, array $payload): VitalSign
    {
        $vitalSign = VitalSign::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $doctor->id,
            'recorded_at' => !empty($payload['recorded_at']) ? Carbon::parse($payload['recorded_at']) : now(),
            'blood_pressure_systolic' => $payload['blood_pressure_systolic'] ?? null,
            'blood_pressure_diastolic' => $payload['blood_pressure_diastolic'] ?? null,
            'temperature' => $payload['temperature'] ?? null,
            'heart_rate' => $payload['heart_rate'] ?? null,
            'respiratory_rate' => $payload['respiratory_rate'] ?? null,
            'oxygen_saturation' => $payload['oxygen_saturation'] ?? null,
            'weight' => $payload['weight'] ?? null,
            'height' => $payload['height'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
        ]);

        $this->logAccess(
            $this->resolveDoctorUser($doctor),
            $appointment->patient,
            'vital_signs_recorded',
            ['vital_sign_id' => $vitalSign->id],
        );

        return $vitalSign;
    }

    public function generateConsultationPdf(Appointments $appointment, User $requester): array
    {
        $appointment->loadMissing([
            'doctor.user',
            'patient.user',
            'prescriptions.doctor.user',
            'examinations',
            'vitalSigns',
        ]);

        $payload = [
            'appointment' => $this->formatAppointment($appointment),
            'patient' => $this->formatPatient($appointment->patient),
            'doctor' => [
                'id' => $appointment->doctor->id,
                'name' => $appointment->doctor->user->name,
                'crm' => $appointment->doctor->crm,
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        $pdf = Pdf::loadView('pdf.consultation-summary', $payload)->setPaper('a4');

        $timestamp = now();
        $filename = sprintf('consultation-%s.pdf', $timestamp->format('YmdHis'));
        $path = "medical-records/consultations/{$appointment->patient_id}/{$filename}";

        $disk = Storage::disk('public');
        $disk->put($path, $pdf->output());
        $fileSize = $disk->size($path);

        MedicalDocument::create([
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
            'uploaded_by' => $requester->id,
            'appointment_id' => $appointment->id,
            'category' => MedicalDocument::CATEGORY_REPORT,
            'name' => sprintf('Resumo da consulta - %s', $timestamp->format('d/m/Y H:i')),
            'file_path' => $path,
            'file_type' => 'application/pdf',
            'file_size' => $fileSize,
            'description' => 'Resumo automático da consulta',
            'metadata' => [
                'appointment_id' => $appointment->id,
            ],
            'visibility' => MedicalDocument::VISIBILITY_DOCTOR,
        ]);

        return [
            'path' => $path,
            'filename' => $filename,
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

    protected function buildMedicalCertificatePdf(MedicalCertificate $certificate): ?array
    {
        $certificate->loadMissing(['doctor.user', 'patient.user', 'appointment']);

        $payload = [
            'certificate' => $certificate,
            'patient' => $this->formatPatient($certificate->patient),
            'doctor' => [
                'id' => $certificate->doctor->id,
                'name' => $certificate->doctor->user->name,
                'crm' => $certificate->doctor->crm,
            ],
        ];

        $pdf = Pdf::loadView('pdf.medical-certificate', $payload)->setPaper('a4');

        $timestamp = now();
        $filename = sprintf('certificate-%s.pdf', $timestamp->format('YmdHis'));
        $path = "medical-records/certificates/{$certificate->patient_id}/{$filename}";

        $disk = Storage::disk('public');
        $disk->put($path, $pdf->output());
        MedicalDocument::create([
            'patient_id' => $certificate->patient_id,
            'doctor_id' => $certificate->doctor_id,
            'uploaded_by' => $certificate->doctor->user?->id,
            'appointment_id' => $certificate->appointment_id,
            'category' => MedicalDocument::CATEGORY_REPORT,
            'name' => 'Atestado médico',
            'file_path' => $path,
            'file_type' => 'application/pdf',
            'file_size' => $disk->size($path),
            'description' => 'Atestado emitido pela plataforma',
            'metadata' => [
                'certificate_id' => $certificate->id,
            ],
            'visibility' => MedicalDocument::VISIBILITY_SHARED,
        ]);

        return [
            'path' => $path,
            'filename' => $filename,
            'public_path' => "/storage/{$path}",
        ];
    }

    protected function resolveDoctorUser(Doctor $doctor): User
    {
        $user = $doctor->user;

        if (!$user) {
            $user = $doctor->load('user')->user;
        }

        if (!$user) {
            throw new \RuntimeException('Usuário do médico não encontrado.');
        }

        return $user;
    }

    protected function generateVerificationCode(): string
    {
        $length = (int) config('telemedicine.medical_records.verification_code_length', 10);
        do {
            $code = Str::upper(Str::random($length));
        } while (MedicalCertificate::where('verification_code', $code)->exists());

        return $code;
    }
}

