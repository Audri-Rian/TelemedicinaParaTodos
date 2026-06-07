<?php

namespace Tests\Feature\Integrations;

use App\Integrations\Contracts\LabIntegrationInterface;
use App\Integrations\Services\CircuitBreaker;
use App\Integrations\Services\IntegrationService;
use App\Models\Examination;
use App\Models\IntegrationEvent;
use App\Models\IntegrationQueueItem;
use App\Models\IntegrationWebhook;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

/**
 * Testes E2E de resiliência da camada de integração.
 *
 * Cobre:
 *  - Circuit breaker abre após threshold de falhas
 *  - Recovery half-open → closed
 *  - Retry via fila (IntegrationQueueItem) com backoff
 *  - Idempotência em webhook
 *  - Webhooks inbound continuam funcionando com circuit open (circuit só afeta outbound)
 *
 * Usa Redis real — @group redis permite rodar seletivamente no CI de robustez.
 *
 * @group resilience
 * @group redis
 */
class ResilienceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valor sentinela usado para marcar o contador de tentativas half-open como
     * "esgotado" — força `isAvailable()` a retornar false mesmo com circuito
     * aberto. Qualquer número >= half_open_attempts (default: 1) serve.
     */
    private const HALF_OPEN_EXHAUSTED = '999';

    private IntegrationService $service;
    private LabIntegrationInterface $labAdapter;
    private CircuitBreaker $circuitBreaker;
    private PartnerIntegration $partner;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            Redis::ping();
        } catch (\Throwable) {
            $this->markTestSkipped('Redis not available — skipping E2E resilience tests');
        }

        $this->labAdapter = Mockery::mock(LabIntegrationInterface::class);
        $this->circuitBreaker = new CircuitBreaker();

        $this->service = new IntegrationService($this->labAdapter, $this->circuitBreaker);

        $this->partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'slug' => 'resilience-test-lab',
            'capabilities' => ['send_exam_order', 'receive_exam_result', 'webhook_result'],
        ]);

        $this->cleanupRedis($this->partner->id);
    }

    protected function tearDown(): void
    {
        if (isset($this->partner)) {
            $this->cleanupRedis($this->partner->id);
        }
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function cleanupRedis(string $partnerId): void
    {
        Redis::del("circuit_breaker:{$partnerId}");
        Redis::del("circuit_breaker:{$partnerId}:failures");
        Redis::del("circuit_breaker:{$partnerId}:half_open");
    }

    private function createExamWithoutEvents(array $attributes = []): Examination
    {
        return Examination::withoutEvents(fn () => Examination::factory()->create(array_merge([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ], $attributes)));
    }

    // ─── Circuit breaker E2E ─────────────────────────────────────

    public function test_circuit_opens_after_repeated_failures(): void
    {
        Event::fake();

        $threshold = config('integrations.circuit_breaker.laboratory.failure_threshold', 5);

        // Labs falhando em todas as chamadas
        $this->labAdapter->shouldReceive('sendOrder')
            ->andThrow(new \RuntimeException('HTTP 500: Internal Server Error'));

        // Disparar chamadas até atingir o threshold. Passamos partner_integration_id
        // explicitamente para garantir que o IntegrationService use o circuit breaker
        // do $this->partner (senão ele escolhe o primeiro lab ativo disponível, que
        // pode ser outro em ambientes com múltiplos parceiros).
        for ($i = 0; $i < $threshold; $i++) {
            $exam = $this->createExamWithoutEvents([
                'partner_integration_id' => $this->partner->id,
            ]);
            $this->service->sendExamOrder($exam);
        }

        // Circuit deve estar aberto
        $this->assertEquals('open', $this->circuitBreaker->getState($this->partner->id));
    }

    public function test_requests_are_queued_when_circuit_is_open(): void
    {
        Event::fake();

        // Forçar circuito aberto e half-open esgotado (bloqueio total)
        Redis::setex("circuit_breaker:{$this->partner->id}", 300, 'open');
        Redis::setex("circuit_breaker:{$this->partner->id}:half_open", 300, self::HALF_OPEN_EXHAUSTED);

        $this->assertFalse($this->circuitBreaker->isAvailable($this->partner->id));

        // sendOrder do adapter NÃO deve ser chamado — circuito aberto
        $this->labAdapter->shouldNotReceive('sendOrder');

        $exam = $this->createExamWithoutEvents([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->service->sendExamOrder($exam);

        // Item deve ter sido enfileirado para retry
        $this->assertDatabaseHas('integration_queue', [
            'partner_integration_id' => $this->partner->id,
            'operation' => IntegrationQueueItem::OP_SEND_EXAM_ORDER,
            'status' => IntegrationQueueItem::STATUS_QUEUED,
        ]);
    }

    public function test_circuit_recovers_when_success_is_recorded_in_half_open(): void
    {
        // Abrir circuito
        Redis::setex("circuit_breaker:{$this->partner->id}", 300, 'open');

        $this->assertEquals('open', $this->circuitBreaker->getState($this->partner->id));

        // isAvailable em open com half-open livre → retorna true (half-open permitido)
        $this->assertTrue($this->circuitBreaker->isAvailable($this->partner->id));

        // Success em half-open reseta tudo
        $this->circuitBreaker->recordSuccess($this->partner->id);

        $this->assertEquals('closed', $this->circuitBreaker->getState($this->partner->id));
        $this->assertTrue($this->circuitBreaker->isAvailable($this->partner->id));
    }

    public function test_circuit_auto_closes_after_cooling_timeout_expires(): void
    {
        // Aqui não dá pra mockar o tempo com Carbon porque o TTL é gerenciado pelo
        // próprio Redis (`EXPIRE`), não pelo PHP. Usamos TTL curto (1s) + sleep
        // para esperar a expiração real. Para evitar lentidão no CI, este teste
        // roda sob @group redis e pode ser excluído com --exclude-group redis.
        Redis::setex("circuit_breaker:{$this->partner->id}", 1, 'open');
        $this->assertEquals('open', $this->circuitBreaker->getState($this->partner->id));

        sleep(2);

        $this->assertEquals('closed', $this->circuitBreaker->getState($this->partner->id));
        $this->assertTrue($this->circuitBreaker->isAvailable($this->partner->id));
    }

    // ─── Retry / backoff via fila ───────────────────────────────

    public function test_failure_enqueues_item_for_retry(): void
    {
        Event::fake();

        $this->labAdapter->shouldReceive('sendOrder')
            ->once()
            ->andThrow(new \RuntimeException('HTTP 503: Service Unavailable'));

        $exam = $this->createExamWithoutEvents([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->service->sendExamOrder($exam);

        // Um item pendente foi enfileirado, associado ao evento falhado
        $queued = IntegrationQueueItem::where('partner_integration_id', $this->partner->id)->first();
        $this->assertNotNull($queued);
        $this->assertEquals(IntegrationQueueItem::STATUS_QUEUED, $queued->status);
        $this->assertEquals($exam->id, $queued->payload['examination_id']);

        // Evento failed foi criado
        $this->assertDatabaseHas('integration_events', [
            'partner_integration_id' => $this->partner->id,
            'resource_id' => $exam->id,
            'status' => IntegrationEvent::STATUS_FAILED,
        ]);
    }

    public function test_queue_item_uses_retry_config_max_attempts(): void
    {
        Event::fake();

        $configuredMax = config('integrations.retry.send_exam_order.max_attempts', 5);

        $this->labAdapter->shouldReceive('sendOrder')
            ->andThrow(new \RuntimeException('network error'));

        $exam = $this->createExamWithoutEvents([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->service->sendExamOrder($exam);

        $queued = IntegrationQueueItem::where('partner_integration_id', $this->partner->id)->first();
        $this->assertEquals($configuredMax, $queued->max_attempts);
    }

    // ─── Idempotência (webhook) ─────────────────────────────────

    public function test_webhook_idempotency_with_same_key_returns_already_processed(): void
    {
        // Setup webhook HMAC para parceiro
        $secret = 'resilience-hmac-secret';
        $this->partner->webhooks()->create([
            'url' => 'https://example.com/webhook',
            'secret' => $secret,
            'events' => ['exam_result_received'],
            'status' => IntegrationWebhook::STATUS_ACTIVE,
        ]);

        // Exame real existente
        $exam = $this->createExamWithoutEvents([
            'partner_integration_id' => $this->partner->id,
            'status' => Examination::STATUS_IN_PROGRESS,
            'external_id' => 'EXT-IDEMPOTENCY-1',
        ]);

        $payload = [
            'id' => 'EXT-IDEMPOTENCY-1',
            'resourceType' => 'DiagnosticReport',
            'results' => [['name' => 'Glicemia', 'value' => 90, 'unit' => 'mg/dL']],
        ];

        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
        $headers = [
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => $timestamp,
            'Content-Type' => 'application/json',
            'X-Idempotency-Key' => 'resilience-idem-001',
        ];

        // Primeira chamada → processed
        $first = $this->call(
            'POST',
            "/api/v1/public/webhooks/lab/{$this->partner->slug}",
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $body,
        );
        $first->assertOk()->assertJson(['status' => 'processed']);

        // Segunda chamada com mesma idempotency key → already_processed
        $second = $this->call(
            'POST',
            "/api/v1/public/webhooks/lab/{$this->partner->slug}",
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $body,
        );
        $second->assertOk()->assertJson(['status' => 'already_processed']);
    }

    // ─── Webhooks funcionam com circuit breaker aberto ──────────

    public function test_inbound_webhooks_still_work_when_outbound_circuit_is_open(): void
    {
        // Circuito outbound aberto
        Redis::setex("circuit_breaker:{$this->partner->id}", 300, 'open');
        Redis::setex("circuit_breaker:{$this->partner->id}:half_open", 300, self::HALF_OPEN_EXHAUSTED);

        $this->assertFalse($this->circuitBreaker->isAvailable($this->partner->id));

        // Webhook inbound deve continuar funcionando
        $secret = 'inbound-still-works-secret';
        $this->partner->webhooks()->create([
            'url' => 'https://example.com/webhook',
            'secret' => $secret,
            'events' => ['exam_result_received'],
            'status' => IntegrationWebhook::STATUS_ACTIVE,
        ]);

        $exam = $this->createExamWithoutEvents([
            'partner_integration_id' => $this->partner->id,
            'status' => Examination::STATUS_IN_PROGRESS,
            'external_id' => 'EXT-INBOUND-OPEN',
        ]);

        $payload = [
            'id' => 'EXT-INBOUND-OPEN',
            'resourceType' => 'DiagnosticReport',
            'results' => [['name' => 'TSH', 'value' => 2.5, 'unit' => 'mUI/L']],
        ];
        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        $response = $this->call(
            'POST',
            "/api/v1/public/webhooks/lab/{$this->partner->slug}",
            [],
            [],
            [],
            $this->transformHeadersToServerVars([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
                'Content-Type' => 'application/json',
                'X-Idempotency-Key' => 'inbound-open-001',
            ]),
            $body,
        );

        $response->assertOk()->assertJson(['status' => 'processed']);
    }
}
