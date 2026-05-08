<?php

namespace App\Services;

use App\Models\Appointments;
use App\Models\MedicalCertificate;
use App\Models\MedicalDocument;
use App\Models\Patient;
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
}
