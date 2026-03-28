<?php

namespace App\Integrations\DTOs;

use DateTimeImmutable;

/**
 * Dados de validação de uma prescrição (resposta para farmácia).
 */
final readonly class PrescriptionValidationDto
{
    public function __construct(
        public bool $valid,
        public ?string $prescriptionId = null,
        public ?string $doctorName = null,
        public ?string $doctorCrm = null,
        public ?string $patientName = null,
        public ?array $medications = null,
        public ?DateTimeImmutable $validUntil = null,
        public ?string $signatureStatus = null,
        public ?string $reason = null,
    ) {}
}
