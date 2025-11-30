<?php

namespace App\Presenters;

use App\Models\Notification;

class NotificationPresenter
{
    /**
     * Transformar notificação para formato do frontend
     *
     * @param Notification $notification
     * @return array
     */
    public function transform(Notification $notification): array
    {
        try {
            $typeValue = $notification->type instanceof \App\Enums\NotificationType 
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
        } catch (\Throwable $e) {
            \Log::error('Erro ao transformar notificação: ' . $e->getMessage(), [
                'notification_id' => $notification->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Retornar estrutura básica em caso de erro
            return [
                'id' => $notification->id ?? null,
                'type' => 'unknown',
                'title' => 'Notificação',
                'message' => 'Erro ao processar notificação',
                'icon' => 'BellIcon',
                'color' => 'text-gray-500',
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
                \App\Enums\NotificationType::APPOINTMENT_CREATED => 'CalendarIcon',
                \App\Enums\NotificationType::APPOINTMENT_CANCELLED => 'XCircleIcon',
                \App\Enums\NotificationType::APPOINTMENT_RESCHEDULED => 'RefreshCcwIcon',
                \App\Enums\NotificationType::PRESCRIPTION_ISSUED => 'FileTextIcon',
                \App\Enums\NotificationType::EXAMINATION_REQUESTED => 'ClipboardIcon',
                \App\Enums\NotificationType::MEDICAL_CERTIFICATE_ISSUED => 'AwardIcon',
                \App\Enums\NotificationType::APPOINTMENT_REMINDER => 'BellIcon',
                default => 'BellIcon',
            };
        } catch (\Throwable $e) {
            return 'BellIcon';
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
                \App\Enums\NotificationType::APPOINTMENT_CREATED => 'text-blue-500',
                \App\Enums\NotificationType::APPOINTMENT_CANCELLED => 'text-red-500',
                \App\Enums\NotificationType::APPOINTMENT_RESCHEDULED => 'text-yellow-500',
                \App\Enums\NotificationType::PRESCRIPTION_ISSUED => 'text-green-500',
                \App\Enums\NotificationType::EXAMINATION_REQUESTED => 'text-purple-500',
                \App\Enums\NotificationType::MEDICAL_CERTIFICATE_ISSUED => 'text-indigo-500',
                \App\Enums\NotificationType::APPOINTMENT_REMINDER => 'text-orange-500',
                default => 'text-gray-500',
            };
        } catch (\Throwable $e) {
            return 'text-gray-500';
        }
    }
    
    /**
     * Obter o tipo de notificação de forma segura
     */
    private function getNotificationType(Notification $notification): \App\Enums\NotificationType
    {
        try {
            if ($notification->type instanceof \App\Enums\NotificationType) {
                return $notification->type;
            }
            
            if (is_string($notification->type)) {
                try {
                    return \App\Enums\NotificationType::from($notification->type);
                } catch (\ValueError $e) {
                    \Log::warning('Tipo de notificação inválido: ' . $notification->type);
                    return \App\Enums\NotificationType::APPOINTMENT_CREATED;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Erro ao obter tipo de notificação: ' . $e->getMessage());
        }
        
        // Fallback para um tipo padrão
        return \App\Enums\NotificationType::APPOINTMENT_CREATED;
    }

    /**
     * Transformar múltiplas notificações
     *
     * @param \Illuminate\Database\Eloquent\Collection|array $notifications
     * @return array
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

