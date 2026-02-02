<?php

namespace App\MedicalRecord\Infrastructure\ExternalServices;

use App\MedicalRecord\Domain\Contracts\ICPBrasilAdapter;
use App\MedicalRecord\Domain\Exceptions\PrescriptionWithoutSignatureException;
use App\MedicalRecord\Domain\ValueObjects\CertificateSignature;

/**
 * Adapter que SEMPRE lança exceção ao tentar assinar.
 *
 * ATENÇÃO: Este adapter NÃO assina documentos. Use apenas em desenvolvimento
 * ou quando nenhum provedor ICP-Brasil estiver configurado.
 *
 * Para produção, implemente um adapter real que integre com:
 * - Soluti, Certisign, Safeweb, ou outro provedor ICP-Brasil
 * - Certificado A1 (arquivo .pfx) ou A3 (token/smartcard)
 *
 * @see \App\MedicalRecord\Domain\Contracts\ICPBrasilAdapter
 */
final class UnconfiguredICPBrasilAdapter implements ICPBrasilAdapter
{
    public function signDocument(string $documentContent, array $context): CertificateSignature
    {
        throw new PrescriptionWithoutSignatureException(
            'Assinatura digital ICP-Brasil não configurada. Configure um provedor de certificação '
            . '(Soluti, Certisign, Safeweb) em config/icp_brasil.php e registre o adapter em AppServiceProvider.'
        );
    }

    public function isConfigured(): bool
    {
        return false;
    }
}
