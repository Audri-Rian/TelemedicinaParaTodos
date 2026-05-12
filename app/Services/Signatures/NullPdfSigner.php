<?php

namespace App\Services\Signatures;

use App\Contracts\PdfSigner;
use App\Models\Doctor;

/**
 * Driver "null" — dev/staging.
 *
 * Retorna o PDF original sem modificação. Não produz assinatura com validade
 * legal ICP-Brasil. Usado para destravar o fluxo de emissão antes de contratar
 * provedor e configurar certificado A1 em produção.
 *
 * O frontend deve exibir aviso quando hasLegalValidity() == false.
 */
final class NullPdfSigner implements PdfSigner
{
    public function signPdf(string $pdfBytes, Doctor $doctor, string $reason): string
    {
        return $pdfBytes;
    }

    public function name(): string
    {
        return 'null';
    }

    public function hasLegalValidity(): bool
    {
        return false;
    }
}
