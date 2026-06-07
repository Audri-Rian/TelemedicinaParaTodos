<?php

namespace App\Support\Signatures;

use Illuminate\Support\Carbon;

/**
 * Resultado da operação de assinatura de um documento.
 * Imutável — produzido por implementações de DigitalSignatureDriver.
 */
final class SignatureResult
{
    public function __construct(
        public readonly string $hash,
        public readonly string $verificationCode,
        public readonly string $status,
        public readonly Carbon $signedAt,
        public readonly string $driver,
        public readonly array $metadata = [],
    ) {}
}
