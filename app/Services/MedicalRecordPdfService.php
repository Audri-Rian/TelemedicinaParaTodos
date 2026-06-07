<?php

namespace App\Services;

use App\Models\Appointments;
use App\Models\MedicalCertificate;
use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Presenters\MedicalRecordPresenter;
use Barryvdh\DomPDF\Facade\Pdf;

class MedicalRecordPdfService
{
    public function __construct(
        private readonly FileStorageManager $fileStorageManager,
        private readonly MedicalRecordPresenter $presenter,
    ) {}

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
            'appointment' => $this->presenter->appointment($appointment),
            'patient' => $this->presenter->patient($appointment->patient),
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
        $diskDomain = FileStorageManager::DOMAIN_MEDICAL_DOCUMENTS;
        $path = $this->fileStorageManager->buildPath($diskDomain, "consultations/{$appointment->patient_id}/{$filename}");
        $disk = $this->fileStorageManager->disk($diskDomain);
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
                'storage_domain' => $diskDomain,
            ],
            'visibility' => MedicalDocument::VISIBILITY_DOCTOR,
        ]);

        return [
            'path' => $path,
            'filename' => $filename,
            'disk_domain' => $diskDomain,
        ];
    }

    public function generatePdfDocument(
        Patient $patient,
        User $requester,
        array $payload,
        array $filters = [],
        string $visibility = MedicalDocument::VISIBILITY_PATIENT,
    ): array {
        $pdf = Pdf::loadView('pdf.medical-record', $payload)->setPaper('a4');

        $timestamp = now();
        $filename = sprintf('medical-record-%s.pdf', $timestamp->format('YmdHis'));
        $diskDomain = FileStorageManager::DOMAIN_MEDICAL_DOCUMENTS;
        $path = $this->fileStorageManager->buildPath($diskDomain, "exports/{$patient->id}/{$filename}");
        $disk = $this->fileStorageManager->disk($diskDomain);
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
                'storage_domain' => $diskDomain,
            ],
            'visibility' => $visibility,
        ]);

        return [
            'path' => $path,
            'filename' => $filename,
            'disk_domain' => $diskDomain,
        ];
    }

    public function buildMedicalCertificatePdf(MedicalCertificate $certificate): ?array
    {
        $certificate->loadMissing(['doctor.user', 'patient.user', 'appointment']);

        $payload = [
            'certificate' => $certificate,
            'patient' => $this->presenter->patient($certificate->patient),
            'doctor' => [
                'id' => $certificate->doctor->id,
                'name' => $certificate->doctor->user->name,
                'crm' => $certificate->doctor->crm,
            ],
        ];

        $pdf = Pdf::loadView('pdf.medical-certificate', $payload)->setPaper('a4');

        $timestamp = now();
        $filename = sprintf('certificate-%s.pdf', $timestamp->format('YmdHis'));
        $diskDomain = FileStorageManager::DOMAIN_CERTIFICATES;
        $path = $this->fileStorageManager->buildPath($diskDomain, "{$certificate->patient_id}/{$filename}");
        $disk = $this->fileStorageManager->disk($diskDomain);
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
                'storage_domain' => $diskDomain,
            ],
            'visibility' => MedicalDocument::VISIBILITY_SHARED,
        ]);

        return [
            'path' => $path,
            'filename' => $filename,
            'disk_domain' => $diskDomain,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // PAdES pipeline helpers — generate bytes only (no storage, no DB write)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Build certificate PDF bytes without persisting to storage.
     * Use in the sign pipeline: buildCertificatePdfBytes() → signPdf() → persistSignedCertificatePdf()
     */
    public function buildCertificatePdfBytes(MedicalCertificate $certificate): string
    {
        $certificate->loadMissing(['doctor.user', 'patient.user', 'appointment']);

        $payload = [
            'certificate' => $certificate,
            'patient' => $this->presenter->patient($certificate->patient),
            'doctor' => [
                'id' => $certificate->doctor->id,
                'name' => $certificate->doctor->user->name,
                'crm' => $certificate->doctor->crm,
            ],
        ];

        return Pdf::loadView('pdf.medical-certificate', $payload)->setPaper('a4')->output();
    }

    /**
     * Build prescription PDF bytes without persisting to storage.
     * Use in the sign pipeline: buildPrescriptionPdfBytes() → signPdf() → persistSignedPrescriptionPdf()
     */
    public function buildPrescriptionPdfBytes(Prescription $prescription): string
    {
        $prescription->loadMissing(['doctor.user', 'patient.user']);

        $verificationUrl = null;
        if ($prescription->verification_code) {
            $template = config('telemedicine.signature.verification_url_template', '/verify/{code}');
            $url = str_replace('{code}', $prescription->verification_code, $template);
            // Only allow http(s) or relative paths — prevents javascript:/data: injection in PDF links
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) {
                $verificationUrl = $url;
            }
        }

        $payload = [
            'patient' => $this->presenter->patient($prescription->patient),
            'doctor' => [
                'id' => $prescription->doctor->id,
                'name' => $prescription->doctor->user->name,
                'crm' => $prescription->doctor->crm,
            ],
            'medications' => $prescription->medications ?? [],
            'instructions' => $prescription->instructions,
            'validUntil' => $prescription->valid_until,
            'issuedAt' => $prescription->issued_at?->format('d/m/Y H:i'),
            'verificationCode' => $prescription->verification_code,
            'verificationUrl' => $verificationUrl,
            'devMode' => false,
        ];

        return Pdf::loadView('pdf.prescription', $payload)->setPaper('a4')->output();
    }

    /**
     * Persist a signed certificate PDF and update the certificate record.
     * Called after signPdf() in the pipeline.
     */
    public function persistSignedCertificatePdf(
        MedicalCertificate $certificate,
        string $signedPdfBytes,
        bool $hasLegalValidity,
    ): array {
        $filename = sprintf('certificate-signed-%s.pdf', $certificate->id);
        $diskDomain = FileStorageManager::DOMAIN_CERTIFICATES;
        $path = $this->fileStorageManager->buildPath($diskDomain, "{$certificate->patient_id}/{$filename}");
        $disk = $this->fileStorageManager->disk($diskDomain);
        $disk->put($path, $signedPdfBytes);
        $fileSize = $disk->size($path);

        MedicalDocument::updateOrCreate(
            ['file_path' => $path],
            [
                'patient_id' => $certificate->patient_id,
                'doctor_id' => $certificate->doctor_id,
                'uploaded_by' => $certificate->doctor->user?->id,
                'appointment_id' => $certificate->appointment_id,
                'category' => MedicalDocument::CATEGORY_REPORT,
                'name' => 'Atestado médico'.($hasLegalValidity ? ' (assinado ICP-Brasil)' : ''),
                'file_type' => 'application/pdf',
                'file_size' => $fileSize,
                'description' => 'Atestado emitido e assinado digitalmente pela plataforma',
                'metadata' => [
                    'certificate_id' => $certificate->id,
                    'storage_domain' => $diskDomain,
                    'has_legal_validity' => $hasLegalValidity,
                ],
                'visibility' => MedicalDocument::VISIBILITY_SHARED,
            ],
        );

        $certificate->forceFill(['pdf_url' => $path])->save();

        return ['path' => $path, 'filename' => $filename, 'disk_domain' => $diskDomain];
    }

    /**
     * Persist a signed prescription PDF and update the prescription record.
     * Called after signPdf() in the pipeline.
     */
    public function persistSignedPrescriptionPdf(
        Prescription $prescription,
        string $signedPdfBytes,
        bool $hasLegalValidity,
    ): array {
        $filename = sprintf('prescription-signed-%s.pdf', $prescription->id);
        $diskDomain = FileStorageManager::DOMAIN_MEDICAL_DOCUMENTS;
        $path = $this->fileStorageManager->buildPath(
            $diskDomain,
            "prescriptions/{$prescription->patient_id}/{$filename}"
        );
        $disk = $this->fileStorageManager->disk($diskDomain);
        $disk->put($path, $signedPdfBytes);
        $fileSize = $disk->size($path);

        MedicalDocument::updateOrCreate(
            ['file_path' => $path],
            [
                'patient_id' => $prescription->patient_id,
                'doctor_id' => $prescription->doctor_id,
                'uploaded_by' => $prescription->doctor->user?->id,
                'appointment_id' => $prescription->appointment_id,
                'category' => MedicalDocument::CATEGORY_REPORT,
                'name' => 'Receita médica'.($hasLegalValidity ? ' (assinada ICP-Brasil)' : ''),
                'file_type' => 'application/pdf',
                'file_size' => $fileSize,
                'description' => 'Receita emitida e assinada digitalmente pela plataforma',
                'metadata' => [
                    'prescription_id' => $prescription->id,
                    'storage_domain' => $diskDomain,
                    'has_legal_validity' => $hasLegalValidity,
                ],
                'visibility' => MedicalDocument::VISIBILITY_SHARED,
            ],
        );

        $prescription->forceFill(['pdf_path' => $path])->save();

        return ['path' => $path, 'filename' => $filename, 'disk_domain' => $diskDomain];
    }
}
