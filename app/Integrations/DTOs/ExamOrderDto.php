<?php

namespace App\Integrations\DTOs;

use App\Models\Examination;

/**
 * Dados de um pedido de exame para envio ao laboratório.
 *
 * Traduz do modelo interno (Examination) para um formato neutro
 * que o adapter converte para FHIR ServiceRequest ou protocolo do parceiro.
 */
final readonly class ExamOrderDto
{
    public function __construct(
        public string $examinationId,
        public string $patientId,
        public string $doctorId,
        public ?string $appointmentId,
        public string $examName,
        public string $examType,
        public string $requestedAt,
        public ?array $metadata = null,
        public ?string $patientCns = null,
        public ?string $doctorCns = null,
    ) {}

    public static function fromExamination(Examination $examination): self
    {
        $examination->loadMissing(['patient', 'doctor']);

        return new self(
            examinationId: $examination->id,
            patientId: $examination->patient_id,
            doctorId: $examination->doctor_id,
            appointmentId: $examination->appointment_id,
            examName: $examination->name,
            examType: $examination->type,
            // Fallback to current time when requested_at is not set (e.g., exam created without explicit date)
            requestedAt: $examination->requested_at?->toIso8601String() ?? now()->toIso8601String(),
            metadata: $examination->metadata,
            patientCns: $examination->patient?->cns,
            doctorCns: $examination->doctor?->cns,
        );
    }
}
