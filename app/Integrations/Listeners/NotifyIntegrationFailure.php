<?php

namespace App\Integrations\Listeners;

use App\Integrations\Events\IntegrationFailed;
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

    public function handle(IntegrationFailed $event): void
    {
        if ($event->partner === null || $event->event === null) {
            Log::channel('integration')->warning('IntegrationFailed recebido com dados incompletos', [
                'has_partner' => $event->partner !== null,
                'has_event' => $event->event !== null,
                'error' => $event->errorMessage ?? null,
            ]);

            return;
        }

        Log::channel('integration')->error('Integração falhou — notificação para admin', [
            'partner_id' => $event->partner->id,
            'partner_name' => $event->partner->name,
            'event_id' => $event->event->id,
            'event_type' => $event->event->event_type,
            'error' => $event->errorMessage,
        ]);

        // TODO: Implementar notificação real para admins (email, Slack, notificação in-app)
        // quando o módulo de notificações administrativas estiver disponível.
    }
}
