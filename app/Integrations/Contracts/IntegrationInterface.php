<?php

namespace App\Integrations\Contracts;

use App\Models\PartnerIntegration;

/**
 * Contrato base para todos os adapters de integração.
 *
 * Cada tipo de parceiro (laboratório, farmácia, hospital) pode ter
 * contratos mais específicos que estendem este.
 */
interface IntegrationInterface
{
    /**
     * Testa a conectividade com o parceiro.
     *
     * @return array{status: string, message: string}
     */
    public function healthCheck(PartnerIntegration $partner): array;

    /**
     * Retorna as capabilities suportadas por este adapter.
     *
     * @return string[]
     */
    public function getCapabilities(): array;

    /**
     * Retorna o tipo de parceiro que este adapter atende.
     */
    public function getPartnerType(): string;
}
