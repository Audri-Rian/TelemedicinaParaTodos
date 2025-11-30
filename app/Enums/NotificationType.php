<?php

namespace App\Enums;

enum NotificationType: string
{
    case APPOINTMENT_CREATED = 'appointment_created';
    case APPOINTMENT_CANCELLED = 'appointment_cancelled';
    case APPOINTMENT_RESCHEDULED = 'appointment_rescheduled';
    case PRESCRIPTION_ISSUED = 'prescription_issued';
    case EXAMINATION_REQUESTED = 'examination_requested';
    case MEDICAL_CERTIFICATE_ISSUED = 'medical_certificate_issued';
    case APPOINTMENT_REMINDER = 'appointment_reminder';

    /**
     * Obter todos os tipos de notificação
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verificar se o tipo é válido
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::all());
    }
}


