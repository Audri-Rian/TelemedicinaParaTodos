<?php

namespace App\MedicalRecord\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object para código CID-10 (RN-DIAG-001).
 * Formato válido: A00.0 a Z99.9 (letra + 2 dígitos + opcional . + 1 dígito).
 */
final readonly class CID10Code
{
    private const PATTERN = '/^[A-Z]\d{2}(\.\d)?$/';

    private string $value;

    public function __construct(string $value)
    {
        $normalized = self::normalize($value);
        if (! preg_match(self::PATTERN, $normalized)) {
            throw new InvalidArgumentException(
                'Código CID-10 inválido. Esperado formato A00.0 a Z99.9 (ex: A00.0, B20, Z99.9).'
            );
        }
        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function normalize(string $value): string
    {
        $trimmed = trim($value);
        return strtoupper($trimmed);
    }

    public static function isValid(string $value): bool
    {
        try {
            new self($value);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
