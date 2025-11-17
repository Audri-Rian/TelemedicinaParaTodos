<?php

namespace App\Enums;

enum DegreeType: string
{
    case FUNDAMENTAL = 'fundamental';
    case MEDIO = 'medio';
    case GRADUACAO = 'graduacao';
    case POS = 'pos';
    case LIVRE = 'curso_livre';
    case CERTIFICADO = 'certificacao';
    case PROJETO = 'projeto';

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all cases as array with labels
     */
    public static function options(): array
    {
        return [
            self::FUNDAMENTAL->value => 'Ensino Fundamental',
            self::MEDIO->value => 'Ensino Médio',
            self::GRADUACAO->value => 'Graduação',
            self::POS->value => 'Pós-Graduação',
            self::LIVRE->value => 'Curso Livre',
            self::CERTIFICADO->value => 'Certificação',
            self::PROJETO->value => 'Projeto',
        ];
    }

    /**
     * Get label for a specific value
     */
    public static function getLabel(string $value): ?string
    {
        return self::options()[$value] ?? null;
    }

    /**
     * Check if value is valid
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values());
    }
}

