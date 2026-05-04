<?php

namespace App\Integrations\Jobs;

use App\Jobs\SyncPartnerExamResultsJob;
use App\Models\PartnerIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job de sincronização automática — busca resultados de exames pendentes.
 *
 * Executado via cron (padrão: a cada 15 minutos).
 * Para cada laboratório ativo, faz pull dos resultados pendentes.
 */
class SyncExamResults implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public $backoff = 60;

    public function __construct()
    {
        $this->onQueue(config('integrations.queue.name', 'integrations'));
    }

    public function handle(): void
    {
        $partners = PartnerIntegration::active()->laboratories()->get();

        // Dispatch escalonado: 10s entre parceiros + jitter de até 5s para evitar pico simultâneo.
        $partners->each(function (PartnerIntegration $partner, int $index) {
            $delaySeconds = $index * 10 + random_int(0, 5);
            SyncPartnerExamResultsJob::dispatch($partner->id)
                ->delay(now()->addSeconds($delaySeconds));
        });

        Log::channel('integration')->info('Sync em lote escalonado para parceiros ativos', [
            'partners_count' => $partners->count(),
        ]);
    }
}
