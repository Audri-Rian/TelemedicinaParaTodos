<?php

namespace App\Services;

use App\Models\Appointments;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\MedicalCertificate;
use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\VitalSign;
use App\Presenters\MedicalRecordPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MedicalRecordReaderService
{
    public function __construct(
        private readonly MedicalRecordPresenter $presenter,
        private readonly MedicalRecordFilterService $filterService,
    ) {}

    public function getPatientMedicalRecord(Patient $patient, array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $appointments = $this->getAppointmentsForRecord($patient, $normalizedFilters);

        return [
            'patient' => $this->presenter->patient($patient),
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

    public function getDoctorPatientList(Doctor $doctor, array $filters = []): Collection
    {
        $query = Appointments::query()
            ->with([
                'patient.user',
                'patient.medicalDocuments',
            ])
            ->where('doctor_id', $doctor->id);

        $this->filterService->applyDoctorPatientListFilters($query, $filters);

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
                    'patient' => $this->presenter->patient($patient),
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

    public function getDoctorPatientMedicalRecord(Doctor $doctor, Patient $patient, array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $doctorFilters = array_merge($normalizedFilters, ['doctor_id' => $doctor->id]);
        $appointments = $this->getAppointmentsForRecord($patient, $doctorFilters);

        return [
            'patient' => $this->presenter->patient($patient),
            'timeline' => $appointments,
            'consultations' => $appointments,
            'prescriptions' => $this->getPrescriptionsForRecord($patient, $doctorFilters),
            'examinations' => $this->getExaminationsForRecord($patient, $doctorFilters),
            'documents' => $this->getDocumentsForDoctorRecord($patient, $doctor, $normalizedFilters),
            'vital_signs' => $this->getVitalSignsForRecord($patient, $doctorFilters),
            'diagnoses' => $this->getDiagnosesForRecord($patient, $normalizedFilters, $doctor),
            'clinical_notes' => $this->getClinicalNotesForRecord($patient, $normalizedFilters, $doctor),
            'medical_certificates' => $this->getMedicalCertificatesForRecord($patient, $normalizedFilters, $doctor),
            'upcoming_appointments' => $this->getUpcomingAppointments($patient),
            'metrics' => $this->buildMetrics($patient),
            'filters' => $normalizedFilters,
        ];
    }

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

        $this->filterService->applyAppointmentFilters($query, $filters);

        return $query
            ->orderByDesc('scheduled_at')
            ->get()
            ->map(fn (Appointments $appointment) => $this->presenter->appointment($appointment));
    }

    public function getPrescriptionsForRecord(Patient $patient, array $filters = []): Collection
    {
        $query = Prescription::with(['doctor.user'])
            ->where('patient_id', $patient->id);

        $this->filterService->applyPrescriptionFilters($query, $filters);

        return $query
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Prescription $prescription) => $this->presenter->prescription($prescription));
    }

    public function getExaminationsForRecord(Patient $patient, array $filters = []): Collection
    {
        $query = Examination::with(['doctor.user', 'appointment', 'partnerIntegration'])
            ->where('patient_id', $patient->id);

        $this->filterService->applyExaminationFilters($query, $filters);

        return $query
            ->orderByDesc('requested_at')
            ->get()
            ->map(fn (Examination $examination) => $this->presenter->examination($examination));
    }

    public function getDocumentsForRecord(Patient $patient, array $filters = []): Collection
    {
        $query = MedicalDocument::with(['doctor.user', 'uploader'])
            ->where('patient_id', $patient->id);

        $this->filterService->applyDocumentFilters($query, $filters);

        return $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (MedicalDocument $document) => $this->presenter->document($document));
    }

    public function getVitalSignsForRecord(Patient $patient, array $filters = []): Collection
    {
        $query = VitalSign::with(['doctor.user'])
            ->where('patient_id', $patient->id);

        $this->filterService->applyVitalSignFilters($query, $filters);

        return $query
            ->orderByDesc('recorded_at')
            ->limit($filters['vitals_limit'] ?? config('telemedicine.medical_records.vitals_limit', 50))
            ->get()
            ->map(fn (VitalSign $vital) => $this->presenter->vitalSign($vital));
    }

    public function getDiagnosesForRecord(Patient $patient, array $filters = [], ?Doctor $doctor = null): Collection
    {
        $query = Diagnosis::with(['doctor.user', 'appointment'])
            ->where('patient_id', $patient->id);

        $this->filterService->applyDiagnosisFilters($query, $filters, $doctor);

        return $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Diagnosis $diagnosis) => $this->presenter->diagnosis($diagnosis));
    }

    public function getClinicalNotesForRecord(Patient $patient, array $filters = [], ?Doctor $doctor = null): Collection
    {
        $query = ClinicalNote::with(['doctor.user', 'appointment'])
            ->where('patient_id', $patient->id);

        $this->filterService->applyClinicalNoteFilters($query, $filters, $doctor);

        return $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ClinicalNote $note) => $this->presenter->clinicalNote($note));
    }

    public function getMedicalCertificatesForRecord(Patient $patient, array $filters = [], ?Doctor $doctor = null): Collection
    {
        $query = MedicalCertificate::with(['doctor.user', 'appointment'])
            ->where('patient_id', $patient->id);

        $this->filterService->applyMedicalCertificateFilters($query, $filters, $doctor);

        return $query
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (MedicalCertificate $certificate) => $this->presenter->medicalCertificate($certificate));
    }

    public function getUpcomingAppointments(Patient $patient): Collection
    {
        return Appointments::with(['doctor.user', 'doctor.specializations'])
            ->where('patient_id', $patient->id)
            ->whereIn('status', [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->take(config('telemedicine.medical_records.search_limit', 10))
            ->get()
            ->map(fn (Appointments $appointment) => $this->presenter->appointment($appointment));
    }

    public function prepareDataForExport(Patient $patient, array $filters = []): array
    {
        $record = $this->getPatientMedicalRecord($patient, $filters);
        $record['generated_at'] = now()->toIso8601String();

        return $record;
    }

    public function normalizeFilters(array $filters): array
    {
        return $this->filterService->normalize($filters);
    }

    private function getDocumentsForDoctorRecord(Patient $patient, Doctor $doctor, array $filters = []): Collection
    {
        $query = MedicalDocument::with(['doctor.user', 'uploader'])
            ->where('patient_id', $patient->id);

        $this->filterService->applyDocumentFilters($query, $filters);

        $query->where(function (Builder $builder) use ($doctor) {
            $builder->where('visibility', '!=', MedicalDocument::VISIBILITY_PATIENT)
                ->orWhere('doctor_id', $doctor->id);
        });

        return $query
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (MedicalDocument $document) => $this->presenter->document($document));
    }

    private function buildMetrics(Patient $patient): array
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
}
