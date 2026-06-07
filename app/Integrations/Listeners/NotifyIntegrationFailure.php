<?php

namespace App\Integrations\Listeners;

use App\Integrations\Events\IntegrationFailed;
use App\Integrations\Services\IntegrationFailureAlerter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notifica administradores quando uma integração falha.
 */
class NotifyIntegrationFailure implements ShouldQueue
{
    public string $queue;

    public function __construct()
    {
        $this->queue = config('integrations.queue.name', 'integrations');
    }

    public function handle(IntegrationFailed $event, IntegrationFailureAlerter $alerter): void
    {
        if ($event->partner === null || $event->event === null) {
            Log::channel('integration')->warning('IntegrationFailed recebido com dados incompletos', [
                'has_partner' => $event->partner !== null,
                'has_event' => $event->event !== null,
            ]);

            return;
        }

        Log::channel('integration')->error('Integração falhou — notificação para admin', [
            'partner_id' => $event->partner->id,
            'partner_name' => $event->partner->name,
            'event_id' => $event->event->id,
            'event_type' => $event->event->event_type,
        ]);

        $alerter->notify($event);
    }
}
