<?php

namespace App\Presenters;

use App\Models\Appointments;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\Examination;
use App\Models\MedicalCertificate;
use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\VitalSign;

class MedicalRecordPresenter
{
    public function patient(Patient $patient): array
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

    public function appointment(Appointments $appointment): array
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
            'prescriptions' => $appointment->prescriptions->map(fn (Prescription $prescription) => $this->prescription($prescription)),
            'examinations' => $appointment->examinations->map(fn (Examination $examination) => $this->examination($examination)),
            'documents' => $appointment->medicalDocuments->map(fn (MedicalDocument $document) => $this->document($document)),
            'vital_signs' => $appointment->vitalSigns->map(fn (VitalSign $vital) => $this->vitalSign($vital)),
        ];
    }

    public function prescription(Prescription $prescription): array
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

    public function examination(Examination $examination): array
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
            'source' => $examination->source ?? Examination::SOURCE_INTERNAL,
            'partner' => $examination->partnerIntegration ? [
                'id' => $examination->partnerIntegration->id,
                'name' => $examination->partnerIntegration->name,
                'slug' => $examination->partnerIntegration->slug,
            ] : null,
            'received_from_partner_at' => $examination->received_from_partner_at?->toIso8601String(),
        ];
    }

    public function document(MedicalDocument $document): array
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

    public function vitalSign(VitalSign $vital): array
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

    public function diagnosis(Diagnosis $diagnosis): array
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

    public function clinicalNote(ClinicalNote $note): array
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

    public function medicalCertificate(MedicalCertificate $certificate): array
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
}
