<?php

namespace App\Services\Signatures;

use App\Contracts\DigitalSignatureDriver;
use App\Support\Signatures\SignatureResult;
use RuntimeException;

/**
 * Driver ICP-Brasil — produção.
 *
 * STUB. Contrato de provedor de certificação (Soluti/Certisign/Safeweb) ainda
 * não fechado. Quando o provedor for definido:
 *
 * 1. Implementar sign() chamando SDK do provedor (assinatura PAdES/CAdES).
 * 2. Armazenar certificado e-CNPJ/e-CPF em config('telemedicine.signature.icp_brasil').
 * 3. Em verify(), validar cadeia X.509 contra ICP-Brasil root CAs.
 * 4. hasLegalValidity() = true (Resolução CFM 2.314/2022 Art. 8).
 *
 * Configuração esperada (config/telemedicine.php):
 *   'signature' => [
 *     'icp_brasil' => [
 *       'provider' => 'soluti',
 *       'certificate_path' => env('ICP_CERT_PATH'),
 *       'certificate_password' => env('ICP_CERT_PASSWORD'),
 *       'api_endpoint' => env('ICP_API_ENDPOINT'),
 *       'api_key' => env('ICP_API_KEY'),
 *     ],
 *   ]
 */
final class IcpBrasilSignatureDriver implements DigitalSignatureDriver
{
    public function sign(string $canonicalPayload): SignatureResult
    {
        throw new RuntimeException(
            'IcpBrasilSignatureDriver não implementado. '.
            'Contratar provedor (Soluti/Certisign/Safeweb) e implementar integração. '.
            'Documentos emitidos com este driver requerem certificado e-CPF/e-CNPJ válido.',
        );
    }

    public function verify(string $canonicalPayload, string $hash): bool
    {
        throw new RuntimeException('IcpBrasilSignatureDriver::verify não implementado.');
    }

    public function name(): string
    {
        return 'icp_brasil';
    }

    public function hasLegalValidity(): bool
    {
        return true;
    }
}
