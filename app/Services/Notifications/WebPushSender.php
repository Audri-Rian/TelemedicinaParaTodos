<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\PushNotificationSender;
use App\Models\Notification;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class WebPushSender implements PushNotificationSender
{
    public function send(Notification $notification): void
    {
        if (! $this->hasVapidConfig()) {
            Log::warning('Push notification skipped because VAPID config is incomplete', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'driver' => 'webpush',
            ]);

            return;
        }

        $subscriptions = PushSubscription::query()
            ->where('user_id', $notification->user_id)
            ->get();

        if ($subscriptions->isEmpty()) {
            Log::debug('Push notification skipped because user has no subscriptions', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'driver' => 'webpush',
            ]);

            return;
        }

        try {
            $webPush = new WebPush($this->auth(), [
                'TTL' => (int) config('telemedicine.push.ttl_seconds', 3600),
            ], (int) config('telemedicine.push.timeout_seconds', 10));

            $payload = $this->payload($notification);
        } catch (Throwable $exception) {
            Log::warning('Push notification skipped because WebPush could not be initialized', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'driver' => 'webpush',
            ]);

            return;
        }

        foreach ($subscriptions as $subscription) {
            $this->sendToSubscription($webPush, $subscription, $notification, $payload);
        }
    }

    private function sendToSubscription(
        WebPush $webPush,
        PushSubscription $subscription,
        Notification $notification,
        string $payload
    ): void {
        try {
            $report = $webPush->sendOneNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding,
                ]),
                $payload
            );

            if ($report->isSuccess()) {
                $subscription->forceFill(['last_used_at' => now()])->save();

                Log::info('Push notification sent', [
                    'notification_id' => $notification->id,
                    'user_id' => $notification->user_id,
                    'subscription_id' => $subscription->id,
                    'driver' => 'webpush',
                ]);

                return;
            }

            if ($report->isSubscriptionExpired()) {
                $subscription->delete();
            }

            Log::warning('Push notification failed', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'subscription_id' => $subscription->id,
                'expired' => $report->isSubscriptionExpired(),
                'status' => $report->getResponse()?->getStatusCode(),
                'reason' => $report->getReason(),
                'driver' => 'webpush',
            ]);
        } catch (Throwable $exception) {
            Log::warning('Push notification failed with exception', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'subscription_id' => $subscription->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'driver' => 'webpush',
            ]);
        }
    }

    private function payload(Notification $notification): string
    {
        return json_encode([
            'type' => $notification->type instanceof \BackedEnum
                ? $notification->type->value
                : (string) $notification->type,
            'title' => $notification->title,
            'body' => 'Voce tem uma nova notificacao.',
            'data' => [
                'notification_id' => $notification->id,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function auth(): array
    {
        return [
            'VAPID' => [
                'subject' => (string) config('telemedicine.push.vapid_subject'),
                'publicKey' => (string) config('telemedicine.push.vapid_public_key'),
                'privateKey' => (string) config('telemedicine.push.vapid_private_key'),
            ],
        ];
    }

    private function hasVapidConfig(): bool
    {
        return filled(config('telemedicine.push.vapid_subject'))
            && filled(config('telemedicine.push.vapid_public_key'))
            && filled(config('telemedicine.push.vapid_private_key'));
    }
}
