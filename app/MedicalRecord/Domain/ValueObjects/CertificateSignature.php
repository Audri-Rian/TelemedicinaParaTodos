<?php

namespace App\MedicalRecord\Domain\ValueObjects;

/**
 * DTO para resultado de assinatura digital ICP-Brasil.
 */
final readonly class CertificateSignature
{
    public function __construct(
        private string $signatureHash,
        private string $verificationCode,
    ) {
    }

    public function signatureHash(): string
    {
        return $this->signatureHash;
    }

    public function verificationCode(): string
    {
        return $this->verificationCode;
    }
}
