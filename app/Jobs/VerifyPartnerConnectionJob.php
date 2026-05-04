<?php

namespace App\Jobs;

use App\Models\PartnerIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerifyPartnerConnectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 60;

    public function __construct(
        public readonly string $partnerId,
        public readonly string $baseUrl,
    ) {
        $this->onQueue(config('integrations.queue.name', 'integrations'));
    }

    public function handle(): void
    {
        $partner = PartnerIntegration::query()->find($this->partnerId);

        if (! $partner || $partner->isActive()) {
            return;
        }

        try {
            $response = Http::timeout(5)
                ->get(rtrim($this->baseUrl, '/').'/metadata');

            if ($response->successful()) {
                $partner->update(['status' => PartnerIntegration::STATUS_ACTIVE]);
            }
        } catch (\Throwable $exception) {
            Log::channel('integration')->warning('Falha ao verificar conexão inicial do parceiro', [
                'partner_id' => $this->partnerId,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
