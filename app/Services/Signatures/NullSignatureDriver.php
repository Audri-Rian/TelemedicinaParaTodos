<?php

namespace App\Services\Signatures;

use App\Contracts\DigitalSignatureDriver;
use App\Support\Signatures\SignatureResult;
use Illuminate\Support\Str;

/**
 * Driver "null" — dev/staging.
 *
 * Gera hash SHA-256 e verification_code aleatório. NÃO produz assinatura
 * com validade legal CFM/ICP-Brasil. Usado para destravar fluxo de emissão
 * em ambientes não-produção, antes de contratar provedor (Soluti/Certisign/Safeweb).
 *
 * Em produção real, vincular config('telemedicine.signature.driver') ao
 * IcpBrasilSignatureDriver.
 */
final class NullSignatureDriver implements DigitalSignatureDriver
{
    public function sign(string $canonicalPayload): SignatureResult
    {
        $hash = hash('sha256', $canonicalPayload);
        $verificationCode = strtoupper(Str::random(12));

        return new SignatureResult(
            hash: $hash,
            verificationCode: $verificationCode,
            status: 'signed',
            signedAt: now(),
            driver: $this->name(),
            metadata: ['legal_validity' => false],
        );
    }

    public function verify(string $canonicalPayload, string $hash): bool
    {
        return hash_equals($hash, hash('sha256', $canonicalPayload));
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
