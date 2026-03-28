<?php

namespace App\Integrations\Contracts;

use App\Integrations\DTOs\PrescriptionValidationDto;
use App\Models\PartnerIntegration;
use App\Models\Prescription;

/**
 * Contrato para adapters de farmácia.
 *
 * Define as operações que qualquer farmácia parceira deve suportar.
 */
interface PharmacyIntegrationInterface extends IntegrationInterface
{
    /**
     * Envia uma prescrição para a farmácia.
     *
     * @return array{external_id: string, status: string}
     */
    public function sendPrescription(PartnerIntegration $partner, Prescription $prescription): array;

    /**
     * Verifica/valida uma prescrição (farmácia consulta nosso sistema).
     */
    public function validatePrescription(string $verificationCode): PrescriptionValidationDto;
}
