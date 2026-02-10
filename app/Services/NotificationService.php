<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Events\NotificationCreated;
use App\Factories\NotificationFactory;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class NotificationService
{
    /**
     * Criar e enviar notificação com debounce
     *
     * @param NotificationType $type
     * @param array $metadata
     * @param User|string $user
     * @param array $channels ['email', 'in_app', 'push']
     * @param bool $skipDebounce Pular debounce (usado internamente)
     * @return Notification|null
     */
    public function create(
        NotificationType $type,
        array $metadata,
        User|string $user,
        array $channels = ['in_app'],
        bool $skipDebounce = false
    ): ?Notification {
        $userId = $user instanceof User ? $user->id : $user;
        $user = $user instanceof User ? $user : User::findOrFail($userId);

        // Verificar debounce (a menos que seja explicitamente ignorado)
        if (!$skipDebounce && $this->shouldDebounce($userId, $type, $metadata)) {
            $this->scheduleDebounce($userId, $type, $metadata, $channels);
            return null;
        }

        // Verificar preferências do usuário
        if (!$this->shouldNotify($user, $type, 'in_app')) {
            return null;
        }

        // Criar notificação
        $notification = NotificationFactory::make($type, $metadata, $user);

        // Enviar por outros canais
        foreach ($channels as $channel) {
            if ($channel !== 'in_app' && $this->shouldNotify($user, $type, $channel)) {
                $this->sendByChannel($notification, $channel);
            }
        }

        // Broadcast em tempo real
        event(new NotificationCreated($notification));

        return $notification;
    }

    /**
     * Verificar se deve aplicar debounce
     */
    private function shouldDebounce(string $userId, NotificationType $type, array $metadata): bool
    {
        // Aplicar debounce apenas para tipos específicos
        $debounceableTypes = [
            NotificationType::APPOINTMENT_RESCHEDULED,
            NotificationType::APPOINTMENT_CANCELLED,
        ];

        if (!in_array($type, $debounceableTypes)) {
            return false;
        }

        // Verificar se já existe uma chave de debounce ativa
        $key = $this->getDebounceKey($userId, $type, $metadata);
        
        return Redis::exists($key);
    }

    /**
     * Agendar debounce
     */
    private function scheduleDebounce(string $userId, NotificationType $type, array $metadata, array $channels): void
    {
        $key = $this->getDebounceKey($userId, $type, $metadata);
        $ttl = (int) config('telemedicine.notifications.debounce_ttl_seconds', 10);

        // Armazenar dados para consolidar depois
        Redis::setex($key, $ttl, json_encode([
            'user_id' => $userId,
            'type' => $type->value,
            'metadata' => $metadata,
            'channels' => $channels,
            'count' => Redis::get($key) ? (int)json_decode(Redis::get($key))->count + 1 : 1,
        ]));

        // Agendar job para processar após TTL
        \App\Jobs\DebounceNotifications::dispatch($userId, $type, $metadata, $channels)
            ->delay(now()->addSeconds($ttl));
    }

    /**
     * Obter chave de debounce
     */
    private function getDebounceKey(string $userId, NotificationType $type, array $metadata): string
    {
        // Usar appointment_id como contexto se disponível
        $context = $metadata['appointment_id'] ?? 'general';
        return "notification_debounce:{$userId}:{$type->value}:{$context}";
    }

    /**
     * Verificar se deve notificar baseado nas preferências
     */
    public function shouldNotify(User $user, NotificationType $type, string $channel): bool
    {
        // Verificar preferência específica
        $preference = NotificationPreference::where('user_id', $user->id)
            ->where('channel', $channel)
            ->where(function ($query) use ($type) {
                $query->where('type', $type->value)
                    ->orWhere('type', NotificationPreference::TYPE_ALL);
            })
            ->first();

        if ($preference) {
            return $preference->isEnabled();
        }

        // Padrão: habilitado para todos os canais e tipos
        return true;
    }

    /**
     * Enviar notificação por canal específico
     */
    private function sendByChannel(Notification $notification, string $channel): void
    {
        match ($channel) {
            'email' => $this->sendEmail($notification),
            'push' => $this->sendPush($notification),
            default => null,
        };
    }

    /**
     * Enviar notificação por email
     */
    private function sendEmail(Notification $notification): void
    {
        $mailableClass = $this->getMailableClass($notification->type);
        
        if ($mailableClass && class_exists($mailableClass)) {
            \Illuminate\Support\Facades\Mail::to($notification->user)
                ->queue(new $mailableClass($notification));
        }
    }

    /**
     * Enviar notificação push (placeholder)
     */
    private function sendPush(Notification $notification): void
    {
        // Implementar quando tiver integração com push notifications
    }

    /**
     * Obter classe Mailable baseada no tipo
     */
    private function getMailableClass(NotificationType $type): ?string
    {
        return match ($type) {
            NotificationType::APPOINTMENT_CREATED => \App\Mail\AppointmentConfirmationMail::class,
            NotificationType::APPOINTMENT_CANCELLED => \App\Mail\AppointmentCancellationMail::class,
            NotificationType::APPOINTMENT_RESCHEDULED => \App\Mail\AppointmentRescheduledMail::class,
            NotificationType::PRESCRIPTION_ISSUED => \App\Mail\PrescriptionIssuedMail::class,
            NotificationType::EXAMINATION_REQUESTED => \App\Mail\ExaminationRequestedMail::class,
            NotificationType::MEDICAL_CERTIFICATE_ISSUED => \App\Mail\MedicalCertificateIssuedMail::class,
            NotificationType::APPOINTMENT_REMINDER => \App\Mail\AppointmentReminderMail::class,
        };
    }

    /**
     * Marcar notificação como lida
     */
    public function markAsRead(Notification $notification): bool
    {
        return $notification->markAsRead();
    }

    /**
     * Marcar todas as notificações do usuário como lidas
     */
    public function markAllAsRead(User|string $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Obter notificações não lidas do usuário
     */
    public function getUnread(User|string $user, ?int $limit = null)
    {
        $limit = $limit ?? (int) config('telemedicine.notifications.list_limit', 10);
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obter contador de notificações não lidas
     */
    public function getUnreadCount(User|string $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}

