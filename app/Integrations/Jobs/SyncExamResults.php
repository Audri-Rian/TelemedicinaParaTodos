<?php

namespace App\Integrations\Jobs;

use App\Integrations\Services\IntegrationService;
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

    public function handle(IntegrationService $service): void
    {
        $partners = PartnerIntegration::active()->laboratories()->get();
        $failures = 0;

        foreach ($partners as $partner) {
            try {
                $received = $service->syncExamResults($partner);

                if ($received > 0) {
                    Log::channel('integration')->info('Sync concluído', [
                        'partner_id' => $partner->id,
                        'partner_name' => $partner->name,
                        'results_received' => $received,
                    ]);
                } else {
                    Log::channel('integration')->debug('Sync concluído - sem resultados', [
                        'partner_id' => $partner->id,
                        'partner_name' => $partner->name,
                    ]);
                }
            } catch (\Throwable $e) {
                $failures++;
                Log::channel('integration')->error('Erro no sync de resultados', [
                    'partner_id' => $partner->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($failures > 0) {
            throw new \RuntimeException("Sync de resultados falhou para {$failures} parceiro(s).");
        }
    }
}
