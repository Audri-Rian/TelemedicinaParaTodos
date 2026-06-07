<?php

namespace App\Presenters;

use App\Enums\NotificationType;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;
use ValueError;

class NotificationPresenter
{
    /**
     * Transformar notificação para formato do frontend
     */
    public function transform(Notification $notification): array
    {
        try {
            $typeValue = $notification->type instanceof NotificationType
                ? $notification->type->value
                : (is_string($notification->type) ? $notification->type : 'unknown');

            return [
                'id' => $notification->id,
                'type' => $typeValue,
                'title' => $this->title($notification),
                'message' => $this->message($notification),
                'icon' => $this->icon($notification),
                'color' => $this->color($notification),
                'time' => $notification->created_at?->diffForHumans() ?? 'agora',
                'timestamp' => $notification->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'metadata' => $notification->metadata ?? [],
                'is_read' => $notification->isRead(),
                'read_at' => $notification->read_at?->toIso8601String(),
            ];
        } catch (Throwable $e) {
            Log::error('Erro ao transformar notificação: '.$e->getMessage(), [
                'notification_id' => $notification->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'id' => $notification->id ?? null,
                'type' => 'unknown',
                'title' => 'Notificação',
                'message' => 'Erro ao processar notificação',
                'icon' => 'bell',
                'color' => 'gray',
                'time' => 'agora',
                'timestamp' => now()->toIso8601String(),
                'metadata' => [],
                'is_read' => false,
                'read_at' => null,
            ];
        }
    }

    /**
     * Obter título formatado
     */
    private function title(Notification $notification): string
    {
        return $notification->title;
    }

    /**
     * Obter mensagem formatada
     */
    private function message(Notification $notification): string
    {
        return $notification->message;
    }

    /**
     * Obter ícone baseado no tipo
     */
    private function icon(Notification $notification): string
    {
        try {
            $type = $this->getNotificationType($notification);

            return match ($type) {
                NotificationType::APPOINTMENT_CREATED => 'calendar-plus',
                NotificationType::APPOINTMENT_CANCELLED => 'calendar-x',
                NotificationType::APPOINTMENT_RESCHEDULED => 'calendar-clock',
                NotificationType::PRESCRIPTION_ISSUED => 'prescription',
                NotificationType::EXAMINATION_REQUESTED => 'clipboard-list',
                NotificationType::MEDICAL_CERTIFICATE_ISSUED => 'file-text',
                NotificationType::APPOINTMENT_REMINDER => 'bell',
                default => 'bell',
            };
        } catch (Throwable) {
            return 'bell';
        }
    }

    /**
     * Obter cor baseada no tipo
     */
    private function color(Notification $notification): string
    {
        try {
            $type = $this->getNotificationType($notification);

            return match ($type) {
                NotificationType::APPOINTMENT_CREATED => 'blue',
                NotificationType::APPOINTMENT_CANCELLED => 'red',
                NotificationType::APPOINTMENT_RESCHEDULED => 'yellow',
                NotificationType::PRESCRIPTION_ISSUED => 'green',
                NotificationType::EXAMINATION_REQUESTED => 'purple',
                NotificationType::MEDICAL_CERTIFICATE_ISSUED => 'indigo',
                NotificationType::APPOINTMENT_REMINDER => 'orange',
                default => 'gray',
            };
        } catch (Throwable) {
            return 'gray';
        }
    }

    /**
     * Obter o tipo de notificação de forma segura
     */
    private function getNotificationType(Notification $notification): NotificationType
    {
        try {
            if ($notification->type instanceof NotificationType) {
                return $notification->type;
            }

            if (is_string($notification->type)) {
                try {
                    return NotificationType::from($notification->type);
                } catch (ValueError) {
                    Log::warning('Tipo de notificação inválido: '.$notification->type);

                    return NotificationType::APPOINTMENT_CREATED;
                }
            }
        } catch (Throwable $e) {
            Log::warning('Erro ao obter tipo de notificação: '.$e->getMessage());
        }

        return NotificationType::APPOINTMENT_CREATED;
    }

    /**
     * Transformar múltiplas notificações
     *
     * @param  \Illuminate\Database\Eloquent\Collection|array  $notifications
     */
    public function transformMany($notifications): array
    {
        if (empty($notifications) || (is_countable($notifications) && count($notifications) === 0)) {
            return [];
        }

        if (is_array($notifications)) {
            return array_map(fn ($notification) => $this->transform($notification), $notifications);
        }

        return $notifications->map(fn ($notification) => $this->transform($notification))->toArray();
    }
}
