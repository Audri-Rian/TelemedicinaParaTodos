<?php

namespace App\Factories;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;

class NotificationFactory
{
    /**
     * Criar uma notificação padronizada
     *
     * @param NotificationType $type
     * @param array $metadata Dados contextuais (appointment_id, doctor_name, etc.)
     * @param User|string $user User model ou user_id
     * @return Notification
     */
    public static function make(NotificationType $type, array $metadata, User|string $user): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        // Obter título e mensagem baseado no tipo
        [$title, $message] = self::getTitleAndMessage($type, $metadata);

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Obter título e mensagem baseado no tipo de notificação
     *
     * @param NotificationType $type
     * @param array $metadata
     * @return array [title, message]
     */
    private static function getTitleAndMessage(NotificationType $type, array $metadata): array
    {
        return match ($type) {
            NotificationType::APPOINTMENT_CREATED => [
                'Consulta Agendada',
                self::formatAppointmentCreatedMessage($metadata),
            ],
            NotificationType::APPOINTMENT_CANCELLED => [
                'Consulta Cancelada',
                self::formatAppointmentCancelledMessage($metadata),
            ],
            NotificationType::APPOINTMENT_RESCHEDULED => [
                'Consulta Reagendada',
                self::formatAppointmentRescheduledMessage($metadata),
            ],
            NotificationType::PRESCRIPTION_ISSUED => [
                'Prescrição Emitida',
                self::formatPrescriptionIssuedMessage($metadata),
            ],
            NotificationType::EXAMINATION_REQUESTED => [
                'Exame Solicitado',
                self::formatExaminationRequestedMessage($metadata),
            ],
            NotificationType::MEDICAL_CERTIFICATE_ISSUED => [
                'Atestado Emitido',
                self::formatMedicalCertificateIssuedMessage($metadata),
            ],
            NotificationType::APPOINTMENT_REMINDER => [
                'Lembrete de Consulta',
                self::formatAppointmentReminderMessage($metadata),
            ],
        };
    }

    private static function formatAppointmentCreatedMessage(array $metadata): string
    {
        $doctorName = $metadata['doctor_name'] ?? 'Médico';
        $scheduledAt = $metadata['scheduled_at'] ?? 'data não informada';
        
        if (is_string($scheduledAt) && strtotime($scheduledAt)) {
            $scheduledAt = date('d/m/Y H:i', strtotime($scheduledAt));
        }
        
        return "Sua consulta com Dr(a). {$doctorName} foi agendada para {$scheduledAt}.";
    }

    private static function formatAppointmentCancelledMessage(array $metadata): string
    {
        $doctorName = $metadata['doctor_name'] ?? 'Médico';
        $reason = $metadata['reason'] ?? null;
        
        $message = "Sua consulta com Dr(a). {$doctorName} foi cancelada.";
        
        if ($reason) {
            $message .= " Motivo: {$reason}";
        }
        
        return $message;
    }

    private static function formatAppointmentRescheduledMessage(array $metadata): string
    {
        $doctorName = $metadata['doctor_name'] ?? 'Médico';
        $oldDate = $metadata['old_scheduled_at'] ?? 'data anterior';
        $newDate = $metadata['new_scheduled_at'] ?? 'nova data';
        
        if (is_string($oldDate) && strtotime($oldDate)) {
            $oldDate = date('d/m/Y H:i', strtotime($oldDate));
        }
        if (is_string($newDate) && strtotime($newDate)) {
            $newDate = date('d/m/Y H:i', strtotime($newDate));
        }
        
        return "Sua consulta com Dr(a). {$doctorName} foi reagendada de {$oldDate} para {$newDate}.";
    }

    private static function formatPrescriptionIssuedMessage(array $metadata): string
    {
        $doctorName = $metadata['doctor_name'] ?? 'Médico';
        return "Dr(a). {$doctorName} emitiu uma nova prescrição para você.";
    }

    private static function formatExaminationRequestedMessage(array $metadata): string
    {
        $doctorName = $metadata['doctor_name'] ?? 'Médico';
        $examinationName = $metadata['examination_name'] ?? 'exame';
        return "Dr(a). {$doctorName} solicitou o exame: {$examinationName}.";
    }

    private static function formatMedicalCertificateIssuedMessage(array $metadata): string
    {
        $doctorName = $metadata['doctor_name'] ?? 'Médico';
        return "Dr(a). {$doctorName} emitiu um atestado médico para você.";
    }

    private static function formatAppointmentReminderMessage(array $metadata): string
    {
        $doctorName = $metadata['doctor_name'] ?? 'Médico';
        $scheduledAt = $metadata['scheduled_at'] ?? 'data não informada';
        $timeUntil = $metadata['time_until'] ?? null;
        
        if (is_string($scheduledAt) && strtotime($scheduledAt)) {
            $scheduledAt = date('d/m/Y H:i', strtotime($scheduledAt));
        }
        
        $message = "Lembrete: Você tem uma consulta com Dr(a). {$doctorName} em {$scheduledAt}.";
        
        if ($timeUntil) {
            $message .= " Faltam {$timeUntil}.";
        }
        
        return $message;
    }
}


