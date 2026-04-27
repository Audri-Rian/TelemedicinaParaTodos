<?php

namespace App\Services\Signatures;

use App\Contracts\DigitalSignatureDriver;
use App\Models\MedicalCertificate;
use App\Models\Prescription;
use App\Support\Signatures\CanonicalPayloadBuilder;
use App\Support\Signatures\SignatureResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fachada de assinatura digital.
 *
 * Aplica driver configurado (config('telemedicine.signature.driver')) sobre
 * Prescription e MedicalCertificate, persiste hash + verification_code +
 * signature_status + signed_at e registra auditoria.
 */
final class DigitalSignatureService
{
    public function __construct(private readonly DigitalSignatureDriver $driver) {}

    public function signPrescription(Prescription $prescription): Prescription
    {
        $payload = CanonicalPayloadBuilder::fromPrescription($prescription);
        $result = $this->driver->sign($payload);

        return DB::transaction(function () use ($prescription, $result) {
            $prescription->forceFill([
                'digital_signature_hash' => $result->hash,
                'verification_code' => $result->verificationCode,
                'signature_status' => Prescription::SIGNATURE_SIGNED,
                'signed_at' => $result->signedAt,
            ])->save();

            $this->logSign('prescription', $prescription->id, $result);

            return $prescription->fresh();
        });
    }

    public function signCertificate(MedicalCertificate $certificate): MedicalCertificate
    {
        $payload = CanonicalPayloadBuilder::fromCertificate($certificate);
        $result = $this->driver->sign($payload);

        return DB::transaction(function () use ($certificate, $result) {
            $certificate->forceFill([
                'signature_hash' => $result->hash,
                'verification_code' => $certificate->verification_code ?: $result->verificationCode,
                'signature_status' => MedicalCertificate::SIGNATURE_SIGNED,
                'signed_at' => $result->signedAt,
            ])->save();

            $this->logSign('medical_certificate', $certificate->id, $result);

            return $certificate->fresh();
        });
    }

    public function verifyPrescription(Prescription $prescription): bool
    {
        if (! $prescription->digital_signature_hash) {
            return false;
        }
        $payload = CanonicalPayloadBuilder::fromPrescription($prescription);

        return $this->driver->verify($payload, $prescription->digital_signature_hash);
    }

    public function verifyCertificate(MedicalCertificate $certificate): bool
    {
        if (! $certificate->signature_hash) {
            return false;
        }
        $payload = CanonicalPayloadBuilder::fromCertificate($certificate);

        return $this->driver->verify($payload, $certificate->signature_hash);
    }

    public function driverName(): string
    {
        return $this->driver->name();
    }

    public function hasLegalValidity(): bool
    {
        return $this->driver->hasLegalValidity();
    }

    private function logSign(string $documentType, string $documentId, SignatureResult $result): void
    {
        Log::channel(config('logging.default'))->info('document_signed', [
            'document_type' => $documentType,
            'document_id' => $documentId,
            'driver' => $result->driver,
            'verification_code' => $result->verificationCode,
            'signed_at' => $result->signedAt->toIso8601String(),
        ]);
    }
}
