<?php

namespace App\Integrations\Services;

use App\Integrations\Events\IntegrationFailed;
use App\Mail\IntegrationFailureMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class IntegrationFailureAlerter
{
    public function notify(IntegrationFailed $event): void
    {
        $partner = $event->partner;
        $integrationEvent = $event->event;
        $recipients = $this->recipients();

        if ($recipients === []) {
            Log::channel('integration')->warning('Integration failure alert skipped because no recipients are configured', [
                'partner_id' => $partner->id,
                'event_id' => $integrationEvent->id,
                'event_type' => $integrationEvent->event_type,
            ]);

            return;
        }

        $throttleKey = $this->throttleKey((string) $partner->id, (string) $integrationEvent->event_type);
        $ttl = (int) config('integrations.alerts.failure_throttle_seconds', 900);

        if ($ttl > 0 && ! Cache::add($throttleKey, now()->toIso8601String(), $ttl)) {
            Log::channel('integration')->debug('Integration failure alert throttled', [
                'partner_id' => $partner->id,
                'event_id' => $integrationEvent->id,
                'event_type' => $integrationEvent->event_type,
                'throttle_key' => $throttleKey,
            ]);

            return;
        }

        Mail::to($recipients)->queue(
            (new IntegrationFailureMail(
                partner: $partner,
                integrationEvent: $integrationEvent,
                sanitizedError: $this->sanitizeError($event->errorMessage),
            ))->onQueue((string) config('integrations.queue.name', 'integrations'))
        );

        Log::channel('integration')->info('Integration failure alert queued', [
            'partner_id' => $partner->id,
            'event_id' => $integrationEvent->id,
            'event_type' => $integrationEvent->event_type,
            'channels' => ['email'],
        ]);
    }

    private function recipients(): array
    {
        return array_values(array_unique(array_filter(
            config('integrations.alerts.emails', []),
            fn (mixed $email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        )));
    }

    private function sanitizeError(string $message): string
    {
        $stripped = strip_tags($message);
        $squished = preg_replace('/\s+/', ' ', $stripped) ?? '';

        return Str::limit(trim($squished), (int) config('integrations.alerts.error_excerpt_length', 300), '...');
    }

    private function throttleKey(string $partnerId, string $eventType): string
    {
        return 'integration_fail_alert:'.$partnerId.':'.$eventType;
    }
}
