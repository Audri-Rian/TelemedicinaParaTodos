<?php

namespace App\Support\Signatures;

use App\Models\MedicalCertificate;
use App\Models\Prescription;

/**
 * Gera payload canônico (string determinística) para assinatura.
 *
 * Regra: ordenar chaves, normalizar tipos, JSON com flags estáveis.
 * Hash precisa ser reprodutível para verificação posterior.
 */
final class CanonicalPayloadBuilder
{
    public static function fromPrescription(Prescription $prescription): string
    {
        $payload = [
            'type' => 'prescription',
            'id' => $prescription->id,
            'doctor_id' => $prescription->doctor_id,
            'patient_id' => $prescription->patient_id,
            'appointment_id' => $prescription->appointment_id,
            'medications' => $prescription->medications,
            'instructions' => $prescription->instructions,
            'valid_until' => optional($prescription->valid_until)->toIso8601String(),
            'issued_at' => optional($prescription->issued_at)->toIso8601String(),
        ];

        return self::canonicalize($payload);
    }

    public static function fromCertificate(MedicalCertificate $certificate): string
    {
        $payload = [
            'type' => 'medical_certificate',
            'id' => $certificate->id,
            'doctor_id' => $certificate->doctor_id,
            'patient_id' => $certificate->patient_id,
            'appointment_id' => $certificate->appointment_id,
            'certificate_type' => $certificate->type,
            'start_date' => optional($certificate->start_date)->toDateString(),
            'end_date' => optional($certificate->end_date)->toDateString(),
            'days' => $certificate->days,
            'reason' => $certificate->reason,
            'restrictions' => $certificate->restrictions,
            'crm_number' => $certificate->crm_number,
        ];

        return self::canonicalize($payload);
    }

    private static function canonicalize(array $payload): string
    {
        ksort($payload);

        return json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }
}
