<?php

namespace App\Integrations\Listeners;

use App\Events\ExaminationRequested;
use App\Integrations\Services\IntegrationService;
use App\Models\Examination;
use App\Models\PartnerIntegration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Ouve ExaminationRequested e envia o pedido ao laboratório parceiro.
 *
 * Só age se existe pelo menos um laboratório ativo. Caso contrário,
 * o exame continua no fluxo normal (upload manual de resultado).
 */
class SendExamOrderToLab implements ShouldQueue
{
    public string $queue;

    public function __construct()
    {
        $this->queue = config('integrations.queue.name', 'integrations');
    }

    public function handle(ExaminationRequested $event): void
    {
        $examination = $event->examination;

        // Só enviar se o exame é de tipo lab e existe parceiro ativo
        if ($examination->type !== Examination::TYPE_LAB) {
            Log::channel('integration')->debug('Exame ignorado — tipo não é laboratório', [
                'examination_id' => $examination->id,
                'type' => $examination->type,
            ]);

            return;
        }

        $hasActiveLab = PartnerIntegration::active()->laboratories()->exists();

        if (! $hasActiveLab) {
            Log::channel('integration')->debug('Exame ignorado — nenhum laboratório parceiro ativo', [
                'examination_id' => $examination->id,
            ]);

            return;
        }

        try {
            app(IntegrationService::class)->sendExamOrder($examination);
        } catch (\Throwable $e) {
            Log::channel('integration')->error('Falha ao enviar pedido de exame ao laboratório', [
                'examination_id' => $examination->id,
                'type' => $examination->type,
                'exception' => $e->getMessage(),
            ]);

            report($e);
        }
    }
}
