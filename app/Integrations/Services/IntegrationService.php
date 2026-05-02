<?php

namespace App\Integrations\Services;

use App\Integrations\Contracts\LabIntegrationInterface;
use App\Integrations\DTOs\ExamOrderDto;
use App\Integrations\Events\ExamOrderSent;
use App\Integrations\Events\IntegrationFailed;
use App\Models\Examination;
use App\Models\FhirResourceMapping;
use App\Models\IntegrationEvent;
use App\Models\IntegrationQueueItem;
use App\Models\PartnerIntegration;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de orquestração da camada de interoperabilidade.
 *
 * Coordena adapters, eventos, fila de retry e circuit breaker.
 * Ponto central de entrada para toda operação de integração.
 */
class IntegrationService
{
    public function __construct(
        private readonly LabIntegrationInterface $labAdapter,
        private readonly CircuitBreaker $circuitBreaker,
    ) {}

    /**
     * Envia um pedido de exame ao laboratório parceiro.
     *
     * Chamado pelo listener SendExamOrderToLab quando ExaminationRequested é disparado.
     */
    public function sendExamOrder(Examination $examination): void
    {
        $partner = $this->findLabPartner($examination);

        if (! $partner) {
            Log::channel('integration')->info('Nenhum laboratório parceiro ativo para enviar pedido', [
                'examination_id' => $examination->id,
            ]);

            return;
        }

        // Verificar idempotência — já foi enviado?
        if (FhirResourceMapping::alreadySynced('examination', $examination->id, $partner->id)) {
            Log::channel('integration')->info('Pedido de exame já enviado', [
                'examination_id' => $examination->id,
                'partner_id' => $partner->id,
            ]);

            return;
        }

        // Verificar circuit breaker
        if (! $this->circuitBreaker->isAvailable($partner->id)) {
            $this->enqueue($partner, IntegrationQueueItem::OP_SEND_EXAM_ORDER, [
                'examination_id' => $examination->id,
            ]);

            return;
        }

        $dto = ExamOrderDto::fromExamination($examination);

        $event = IntegrationEvent::create([
            'partner_integration_id' => $partner->id,
            'doctor_id' => $examination->doctor_id,
            'direction' => IntegrationEvent::DIRECTION_OUTBOUND,
            'event_type' => IntegrationEvent::EVENT_EXAM_ORDER_SENT,
            'status' => IntegrationEvent::STATUS_PROCESSING,
            'resource_type' => 'examination',
            'resource_id' => $examination->id,
            'fhir_resource_type' => 'ServiceRequest',
        ]);

        $startTime = microtime(true);

        try {
            $result = $this->labAdapter->sendOrder($partner, $dto);
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Atualizar exame com external_id
            $examination->update([
                'partner_integration_id' => $partner->id,
                'external_id' => $result['external_id'],
                'status' => Examination::STATUS_IN_PROGRESS,
            ]);

            // Registrar mapeamento FHIR
            FhirResourceMapping::create([
                'internal_resource_type' => FhirResourceMapping::INTERNAL_EXAMINATION,
                'internal_resource_id' => $examination->id,
                'fhir_resource_type' => FhirResourceMapping::FHIR_SERVICE_REQUEST,
                'fhir_resource_id' => $result['external_id'],
                'partner_integration_id' => $partner->id,
                'synced_at' => now(),
            ]);

            $event->update([
                'status' => IntegrationEvent::STATUS_SUCCESS,
                'external_id' => $result['external_id'],
                'duration_ms' => $durationMs,
            ]);

            $this->circuitBreaker->recordSuccess($partner->id);
            $partner->update(['last_sync_at' => now()]);

            ExamOrderSent::dispatch($examination, $partner, $result['external_id']);

        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::channel('integration')->error('Falha ao enviar pedido de exame', [
                'examination_id' => $examination->id,
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            $event->update([
                'status' => IntegrationEvent::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'http_status' => method_exists($e, 'getCode') ? $e->getCode() : null,
            ]);

            $this->circuitBreaker->recordFailure($partner->id);

            // Enfileirar para retry
            $this->enqueue($partner, IntegrationQueueItem::OP_SEND_EXAM_ORDER, [
                'examination_id' => $examination->id,
            ], $event);

            IntegrationFailed::dispatch($partner, $event, $e->getMessage());
        }
    }

    /**
     * Busca resultados de exames pendentes de um parceiro (pull/sync).
     */
    public function syncExamResults(PartnerIntegration $partner): int
    {
        if (! $this->circuitBreaker->isAvailable($partner->id)) {
            Log::channel('integration')->info('Circuit open, pulando sync', [
                'partner_id' => $partner->id,
            ]);

            return 0;
        }

        $received = 0;
        $processed = 0;
        $maxPerRun = (int) config('integrations.sync.max_exams_per_run', 200);
        $batchSize = (int) config('integrations.sync.batch_size', 50);

        Examination::query()
            ->where('partner_integration_id', $partner->id)
            ->whereIn('status', [Examination::STATUS_REQUESTED, Examination::STATUS_IN_PROGRESS])
            ->whereNotNull('external_id')
            ->orderBy('id')
            ->chunkById($batchSize, function ($pendingExams) use ($partner, &$received, &$processed, $maxPerRun) {
                foreach ($pendingExams as $examination) {
                    if ($processed >= $maxPerRun) {
                        return false;
                    }

                    $processed++;

                    try {
                        $result = $this->labAdapter->fetchResult($partner, $examination->external_id);

                        if (! $result) {
                            continue;
                        }

                        $updateData = [
                            'status' => Examination::STATUS_COMPLETED,
                            'results' => $result->results,
                            'completed_at' => $result->completedAt ? now()->parse($result->completedAt) : now(),
                            'source' => Examination::SOURCE_INTEGRATION,
                            'received_from_partner_at' => now(),
                            'external_accession' => $result->accessionNumber,
                        ];

                        if ($result->attachmentUrl) {
                            $updateData['attachment_url'] = $result->attachmentUrl;
                        }

                        $examination->update($updateData);

                        IntegrationEvent::create([
                            'partner_integration_id' => $partner->id,
                            'doctor_id' => $examination->doctor_id,
                            'direction' => IntegrationEvent::DIRECTION_INBOUND,
                            'event_type' => IntegrationEvent::EVENT_EXAM_RESULT_RECEIVED,
                            'status' => IntegrationEvent::STATUS_SUCCESS,
                            'resource_type' => 'examination',
                            'resource_id' => $examination->id,
                            'external_id' => $examination->external_id,
                            'fhir_resource_type' => 'DiagnosticReport',
                        ]);

                        $this->circuitBreaker->recordSuccess($partner->id);
                        $received++;

                        \App\Integrations\Events\ExamResultReceived::dispatch($examination->fresh(), $partner);
                    } catch (\Throwable $e) {
                        Log::channel('integration')->warning('Falha ao buscar resultado', [
                            'examination_id' => $examination->id,
                            'error' => $e->getMessage(),
                        ]);

                        $this->circuitBreaker->recordFailure($partner->id);
                    }
                }
            });

        if ($received > 0) {
            $partner->update(['last_sync_at' => now()]);
        }

        return $received;
    }

    /**
     * Processa itens pendentes na fila de integração.
     */
    public function processQueue(): int
    {
        $items = IntegrationQueueItem::pending()
            ->with('partnerIntegration')
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        $processed = 0;

        foreach ($items as $item) {
            if (! $this->circuitBreaker->isAvailable($item->partner_integration_id)) {
                continue;
            }

            $item->markProcessing();

            try {
                $this->executeQueueItem($item);
                $item->markCompleted();
                $processed++;
            } catch (\Throwable $e) {
                $item->markFailed($e->getMessage());

                Log::channel('integration')->warning('Falha ao processar item da fila', [
                    'queue_item_id' => $item->id,
                    'operation' => $item->operation,
                    'attempt' => $item->attempts,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Encontra o laboratório parceiro ativo para enviar o exame.
     */
    private function findLabPartner(Examination $examination): ?PartnerIntegration
    {
        // Se o exame já tem parceiro, usar este
        if ($examination->partner_integration_id) {
            return PartnerIntegration::find($examination->partner_integration_id);
        }

        $doctor = $examination->doctor;
        if (! $doctor) {
            return null;
        }

        // Buscar primeiro laboratório ativo conectado ao médico solicitante
        return $doctor->partnerIntegrations()
            ->where('partner_integrations.status', PartnerIntegration::STATUS_ACTIVE)
            ->where('partner_integrations.type', PartnerIntegration::TYPE_LABORATORY)
            ->get()
            ->first(fn (PartnerIntegration $partner) => $partner->hasCapability('send_exam_order'));
    }

    /**
     * Enfileira uma operação para retry.
     */
    private function enqueue(
        PartnerIntegration $partner,
        string $operation,
        array $payload,
        ?IntegrationEvent $event = null,
    ): void {
        $retryConfig = config("integrations.retry.{$operation}", [
            'max_attempts' => 5,
            'base_delay' => 30,
        ]);

        IntegrationQueueItem::create([
            'partner_integration_id' => $partner->id,
            'integration_event_id' => $event?->id,
            'operation' => $operation,
            'payload' => $payload,
            'status' => IntegrationQueueItem::STATUS_QUEUED,
            'max_attempts' => $retryConfig['max_attempts'],
        ]);
    }

    /**
     * Executa um item da fila.
     */
    private function executeQueueItem(IntegrationQueueItem $item): void
    {
        match ($item->operation) {
            IntegrationQueueItem::OP_SEND_EXAM_ORDER => $this->retrySendExamOrder($item),
            default => throw new \RuntimeException("Operação desconhecida: {$item->operation}"),
        };
    }

    /**
     * Retry de envio de pedido de exame.
     */
    private function retrySendExamOrder(IntegrationQueueItem $item): void
    {
        $examination = Examination::findOrFail($item->payload['examination_id']);
        $this->sendExamOrder($examination);
    }
}
