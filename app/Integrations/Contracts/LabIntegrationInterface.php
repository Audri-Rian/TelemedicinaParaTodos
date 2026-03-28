<?php

namespace App\Integrations\Contracts;

use App\Integrations\DTOs\ExamOrderDto;
use App\Integrations\DTOs\ExamResultDto;
use App\Models\PartnerIntegration;

/**
 * Contrato para adapters de laboratório.
 *
 * Define as operações que qualquer laboratório parceiro deve suportar.
 */
interface LabIntegrationInterface extends IntegrationInterface
{
    /**
     * Envia um pedido de exame ao laboratório.
     *
     * @return array{external_id: string, status: string}
     */
    public function sendOrder(PartnerIntegration $partner, ExamOrderDto $order): array;

    /**
     * Busca o resultado de um exame pelo ID externo.
     */
    public function fetchResult(PartnerIntegration $partner, string $externalId): ?ExamResultDto;

    /**
     * Processa um payload de webhook recebido do laboratório.
     *
     * @throws \InvalidArgumentException When payload is malformed or missing required fields.
     */
    public function parseWebhookPayload(array $payload): ExamResultDto;
}
