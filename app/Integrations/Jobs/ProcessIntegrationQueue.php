<?php

namespace App\Integrations\Jobs;

use App\Integrations\Services\IntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job que processa itens pendentes na fila de integração (retry).
 *
 * Executado via cron (padrão: a cada 5 minutos).
 */
class ProcessIntegrationQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public $backoff = 60;

    public function __construct()
    {
        $this->onQueue(config('integrations.queue.name', 'integrations'));
    }

    public function handle(IntegrationService $service): void
    {
        $processed = $service->processQueue();

        if ($processed > 0) {
            Log::channel('integration')->info('Fila de integração processada', [
                'items_processed' => $processed,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('integration')->error('Falha ao processar fila de integração', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
