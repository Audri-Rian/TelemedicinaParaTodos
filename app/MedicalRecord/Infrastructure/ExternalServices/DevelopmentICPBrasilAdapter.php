<?php

namespace App\MedicalRecord\Infrastructure\ExternalServices;

use App\MedicalRecord\Domain\Contracts\ICPBrasilAdapter;
use App\MedicalRecord\Domain\ValueObjects\CertificateSignature;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Adapter APENAS para desenvolvimento local.
 *
 * ATENÇÃO: NÃO USE EM PRODUÇÃO. Este adapter gera hashes sem assinatura
 * digital real. Prescrições emitidas com este adapter NÃO têm validade legal.
 *
 * Para produção: implemente adapter real (Soluti, Certisign, Safeweb)
 * e configure em config/icp_brasil.php.
 */
final class DevelopmentICPBrasilAdapter implements ICPBrasilAdapter
{
    public function signDocument(string $documentContent, array $context): CertificateSignature
    {
        Log::warning('[ICP-Brasil] DevelopmentICPBrasilAdapter em uso - assinatura NÃO é válida legalmente. Configure provedor real para produção.');

        $hash = hash('sha256', $documentContent.Str::random(16));
        $code = 'DEV-'.Str::upper(Str::random(10));

        return new CertificateSignature($hash, $code);
    }

    public function isConfigured(): bool
    {
        return true;
    }
}
