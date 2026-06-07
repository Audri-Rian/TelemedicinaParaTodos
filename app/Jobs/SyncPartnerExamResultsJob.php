<?php

namespace App\Jobs;

use App\Integrations\Services\IntegrationService;
use App\Models\PartnerIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncPartnerExamResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public int $backoff = 60;

    public function __construct(
        public readonly string $partnerId,
    ) {
        $this->onQueue(config('integrations.queue.name', 'integrations'));
    }

    public function handle(IntegrationService $service): void
    {
        $partner = PartnerIntegration::query()->find($this->partnerId);
        if (! $partner || ! $partner->isActive()) {
            return;
        }

        $lock = Cache::lock("sync_partner_{$this->partnerId}", 600);

        if (! $lock->get()) {
            Log::channel('integration')->info('Sync ignorado: execução anterior ainda em progresso', [
                'partner_id' => $this->partnerId,
            ]);

            return;
        }

        try {
            $received = $service->syncExamResults($partner);

            Log::channel('integration')->info('Sync processado', [
                'partner_id' => $partner->id,
                'received' => $received,
            ]);
        } finally {
            $lock->release();
        }
    }
}
