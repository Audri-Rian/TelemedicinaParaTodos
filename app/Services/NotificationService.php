<?php

namespace App\Services;

use App\Contracts\Notifications\PushNotificationSender;
use App\Enums\NotificationType;
use App\Events\NotificationCreated;
use App\Factories\NotificationFactory;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    private const PREFERENCE_CACHE_TTL_MINUTES = 5;

    private const UNREAD_COUNT_CACHE_TTL_SECONDS = 30;

    public function __construct(
        private PushNotificationSender $pushNotificationSender
    ) {}

    public static function forgetUnreadCountCache(string $userId): void
    {
        Cache::forget(self::unreadCountCacheKey($userId));
    }

    public static function forgetPreferenceCache(string $userId, string $channel): void
    {
        Cache::forget(self::preferenceCacheKey($userId, $channel));
    }

    private static function unreadCountCacheKey(string $userId): string
    {
        return "unread_notifications_count:{$userId}";
    }

    private static function preferenceCacheKey(string $userId, string $channel): string
    {
        return "notification_prefs:{$userId}:{$channel}";
    }

    /**
     * Criar e enviar notificação com debounce
     *
     * @param  array  $channels  ['email', 'in_app', 'push']
     * @param  bool  $skipDebounce  Pular debounce (usado internamente)
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

        // Idempotência para evitar duplicidade de notificação quando o listener
        // for reprocessado (ex.: retry de fila para AppointmentCreated).
        if ($type === NotificationType::APPOINTMENT_CREATED) {
            $existingNotification = $this->findExistingAppointmentCreatedNotification($userId, $metadata);
            if ($existingNotification) {
                return $existingNotification;
            }
        }

        // Verificar debounce (a menos que seja explicitamente ignorado)
        if (! $skipDebounce && $this->shouldDebounce($userId, $type, $metadata)) {
            $this->scheduleDebounce($userId, $type, $metadata, $channels);

            return null;
        }

        // Verificar preferências do usuário
        if (! $this->shouldNotify($user, $type, 'in_app')) {
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

    private function findExistingAppointmentCreatedNotification(string $userId, array $metadata): ?Notification
    {
        $appointmentId = $metadata['appointment_id'] ?? null;

        if (! is_string($appointmentId) || trim($appointmentId) === '') {
            return null;
        }

        return Notification::where('user_id', $userId)
            ->where('type', NotificationType::APPOINTMENT_CREATED->value)
            ->where('metadata->appointment_id', $appointmentId)
            ->first();
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

        if (! in_array($type, $debounceableTypes)) {
            return false;
        }

        // Verificar se já existe uma chave de debounce ativa
        $key = $this->getDebounceKey($userId, $type, $metadata);

        return Cache::has($key);
    }

    /**
     * Agendar debounce
     */
    private function scheduleDebounce(string $userId, NotificationType $type, array $metadata, array $channels): void
    {
        $key = $this->getDebounceKey($userId, $type, $metadata);
        $ttl = (int) config('telemedicine.notifications.debounce_ttl_seconds', 10);

        $existing = Cache::get($key);
        $count = $existing ? (int) json_decode($existing)->count + 1 : 1;

        // Armazenar dados para consolidar depois
        Cache::put($key, json_encode([
            'user_id' => $userId,
            'type' => $type->value,
            'metadata' => $metadata,
            'channels' => $channels,
            'count' => $count,
        ]), $ttl);

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
        $preferences = Cache::remember(
            self::preferenceCacheKey($user->id, $channel),
            now()->addMinutes(self::PREFERENCE_CACHE_TTL_MINUTES),
            fn (): Collection => NotificationPreference::where('user_id', $user->id)
                ->where('channel', $channel)
                ->get(),
        );

        $specific = $preferences->firstWhere('type', $type->value);
        if ($specific) {
            return $specific->isEnabled();
        }

        $allTypes = $preferences->firstWhere('type', NotificationPreference::TYPE_ALL);
        if ($allTypes) {
            return $allTypes->isEnabled();
        }

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

    private function sendPush(Notification $notification): void
    {
        $this->pushNotificationSender->send($notification);
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
            NotificationType::EXAM_RESULT_RECEIVED => null,
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

        $updated = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($updated > 0) {
            self::forgetUnreadCountCache($userId);
        }

        return $updated;
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

        return Cache::remember(
            self::unreadCountCacheKey($userId),
            now()->addSeconds(self::UNREAD_COUNT_CACHE_TTL_SECONDS),
            fn (): int => Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->count(),
        );
    }
}
