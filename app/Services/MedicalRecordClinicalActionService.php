<?php

namespace App\Services;

use App\Jobs\SignAndGenerateCertificatePdfJob;
use App\Jobs\SignPrescriptionJob;
use App\Models\Appointments;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\MedicalCertificate;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\VitalSign;
use App\Services\Signatures\DigitalSignatureService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicalRecordClinicalActionService
{
    public function __construct(
        private readonly MedicalRecordAuditService $auditService,
        private readonly MedicalRecordPdfService $pdfService,
        private readonly ?DigitalSignatureService $signatureService = null,
    ) {}

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

            $this->auditService->logDoctorAction(
                $doctor,
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
            'valid_until' => ! empty($payload['valid_until'])
                ? Carbon::parse($payload['valid_until'])
                : now()->addDays(config('telemedicine.medical_records.prescription_default_validity_days', 30)),
            'status' => Prescription::STATUS_ACTIVE,
            'metadata' => $payload['metadata'] ?? null,
            'issued_at' => now(),
        ]);

        if ($this->signatureService) {
            SignPrescriptionJob::dispatch($prescription->id);
        }

        $this->auditService->logDoctorAction(
            $doctor,
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
            'partner_integration_id' => $payload['partner_integration_id'] ?? null,
            'metadata' => [
                'justification' => $payload['justification'] ?? null,
                'priority' => $payload['priority'] ?? 'normal',
                'instructions' => $payload['instructions'] ?? null,
            ],
        ]);

        $this->auditService->logDoctorAction(
            $doctor,
            $patient,
            'examination_requested',
            ['examination_id' => $examination->id],
        );

        return $examination;
    }

    public function createClinicalNote(Doctor $doctor, Patient $patient, Appointments $appointment, array $payload): ClinicalNote
    {
        $version = 1;
        if (! empty($payload['parent_id'])) {
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

        $this->auditService->logDoctorAction(
            $doctor,
            $patient,
            'clinical_note_created',
            ['note_id' => $note->id],
        );

        return $note;
    }

    public function issueCertificate(Doctor $doctor, Patient $patient, Appointments $appointment, array $payload): MedicalCertificate
    {
        $certificate = MedicalCertificate::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'type' => $payload['type'] ?? MedicalCertificate::TYPE_ATTENDANCE,
            'start_date' => Carbon::parse($payload['start_date']),
            'end_date' => ! empty($payload['end_date']) ? Carbon::parse($payload['end_date']) : null,
            'days' => $payload['days'] ?? 1,
            'reason' => $payload['reason'],
            'restrictions' => $payload['restrictions'] ?? null,
            'signature_hash' => $payload['signature_hash'] ?? null,
            'crm_number' => $doctor->crm,
            'verification_code' => $this->generateVerificationCode(),
            'status' => MedicalCertificate::STATUS_ACTIVE,
            'metadata' => $payload['metadata'] ?? null,
        ]);

        if ($this->signatureService) {
            SignAndGenerateCertificatePdfJob::dispatch($certificate->id);
        } else {
            $pdf = $this->pdfService->buildMedicalCertificatePdf($certificate);
            if ($pdf) {
                $certificate->forceFill(['pdf_url' => null])->save();
            }
        }

        $this->auditService->logDoctorAction(
            $doctor,
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
            'recorded_at' => ! empty($payload['recorded_at']) ? Carbon::parse($payload['recorded_at']) : now(),
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

        $this->auditService->logDoctorAction(
            $doctor,
            $appointment->patient,
            'vital_signs_recorded',
            ['vital_sign_id' => $vitalSign->id],
        );

        return $vitalSign;
    }

    private function generateVerificationCode(): string
    {
        $length = (int) config('telemedicine.medical_records.verification_code_length', 10);
        do {
            $code = Str::upper(Str::random($length));
        } while (MedicalCertificate::where('verification_code', $code)->exists());

        return $code;
    }
}
