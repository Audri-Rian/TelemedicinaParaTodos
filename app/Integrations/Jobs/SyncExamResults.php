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
        $partners->each(fn (PartnerIntegration $partner) => SyncPartnerExamResultsJob::dispatch($partner->id));

        Log::channel('integration')->info('Sync em lote enfileirado para parceiros ativos', [
            'partners_count' => $partners->count(),
        ]);
    }
}
