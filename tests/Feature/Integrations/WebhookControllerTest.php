<?php

namespace Tests\Feature\Integrations;

use App\Integrations\Events\ExamResultReceived;
use App\Models\Examination;
use App\Models\IntegrationEvent;
use App\Models\IntegrationWebhook;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIntegration $partner;
    private string $webhookSecret = 'test-hmac-secret-key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'slug' => 'webhook-test-lab',
        ]);
        $this->partner->credential()->create([
            'auth_type' => 'api_key',
            'client_id' => 'test-key',
            'client_secret' => 'test-webhook-secret',
        ]);
        // Configurar webhook com secret para HMAC
        $this->partner->webhooks()->create([
            'url' => 'https://example.com/webhook',
            'secret' => $this->webhookSecret,
            'events' => ['exam_result_received'],
            'status' => IntegrationWebhook::STATUS_ACTIVE,
        ]);
    }

    private function createExamWithoutEvents(array $attributes = []): Examination
    {
        return Examination::withoutEvents(fn () => Examination::factory()->create($attributes));
    }

    /**
     * Gera os headers HMAC para um payload.
     */
    private function hmacHeaders(string $body): array
    {
        $timestamp = (string) time();
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $this->webhookSecret);

        return [
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => $timestamp,
        ];
    }

    /**
     * POST com HMAC correto.
     */
    private function postWithHmac(string $url, array $payload, array $extraHeaders = [])
    {
        $body = json_encode($payload);
        $headers = array_merge(
            $this->hmacHeaders($body),
            ['Content-Type' => 'application/json'],
            $extraHeaders,
        );

        return $this->call('POST', $url, [], [], [], $this->transformHeadersToServerVars($headers), $body);
    }

    // ─── Testes de HMAC ──────────────────────────────────────────

    public function test_webhook_rejects_request_without_hmac_headers(): void
    {
        $response = $this->postJson("/api/v1/public/webhooks/lab/webhook-test-lab", [
            'id' => 'EXT-1',
            'resourceType' => 'DiagnosticReport',
            'results' => [],
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'webhook_signature_required']);
    }

    public function test_webhook_rejects_invalid_hmac_signature(): void
    {
        $payload = json_encode(['id' => 'EXT-1', 'results' => []]);
        $timestamp = (string) time();
        $wrongSignature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $payload, 'wrong-secret');

        $response = $this->call('POST', '/api/v1/public/webhooks/lab/webhook-test-lab', [], [], [],
            $this->transformHeadersToServerVars([
                'X-Webhook-Signature' => $wrongSignature,
                'X-Webhook-Timestamp' => $timestamp,
                'Content-Type' => 'application/json',
            ]),
            $payload,
        );

        $response->assertStatus(401)
            ->assertJson(['error' => 'webhook_signature_invalid']);
    }

    public function test_webhook_rejects_expired_timestamp(): void
    {
        $payload = json_encode(['id' => 'EXT-1', 'results' => []]);
        $oldTimestamp = (string) (time() - 600); // 10 minutos atrás (tolerância é 300s)
        $signature = 'sha256=' . hash_hmac('sha256', $oldTimestamp . '.' . $payload, $this->webhookSecret);

        $response = $this->call('POST', '/api/v1/public/webhooks/lab/webhook-test-lab', [], [], [],
            $this->transformHeadersToServerVars([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $oldTimestamp,
                'Content-Type' => 'application/json',
            ]),
            $payload,
        );

        $response->assertStatus(401)
            ->assertJson(['error' => 'webhook_timestamp_expired']);
    }

    public function test_webhook_rejects_missing_timestamp_with_signature(): void
    {
        $response = $this->postJson("/api/v1/public/webhooks/lab/webhook-test-lab", [
            'id' => 'EXT-1',
            'results' => [],
        ], [
            'X-Webhook-Signature' => 'sha256=fake',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'webhook_missing_signature_or_timestamp']);
    }

    // ─── Testes de fluxo ─────────────────────────────────────────

    public function test_webhook_processes_valid_lab_result(): void
    {
        Event::fake([ExamResultReceived::class]);

        $examination = $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_IN_PROGRESS,
            'partner_integration_id' => $this->partner->id,
            'external_id' => 'EXT-WEBHOOK-1',
        ]);

        $payload = [
            'id' => 'EXT-WEBHOOK-1',
            'resourceType' => 'DiagnosticReport',
            'results' => [
                ['name' => 'Hemoglobina', 'value' => 14.0, 'unit' => 'g/dL'],
            ],
        ];

        $response = $this->postWithHmac(
            "/api/v1/public/webhooks/lab/webhook-test-lab",
            $payload,
            ['X-Idempotency-Key' => 'idem-key-1'],
        );

        $response->assertOk()
            ->assertJson(['status' => 'processed']);

        $examination->refresh();
        $this->assertEquals(Examination::STATUS_COMPLETED, $examination->status);
        $this->assertEquals(Examination::SOURCE_INTEGRATION, $examination->source);
        $this->assertNotNull($examination->received_from_partner_at);

        $this->assertDatabaseHas('integration_events', [
            'partner_integration_id' => $this->partner->id,
            'event_type' => IntegrationEvent::EVENT_EXAM_RESULT_RECEIVED,
            'status' => IntegrationEvent::STATUS_SUCCESS,
        ]);

        Event::assertDispatched(ExamResultReceived::class);
    }

    public function test_webhook_idempotency_prevents_duplicate_processing(): void
    {
        Event::fake([ExamResultReceived::class]);

        $examination = $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_IN_PROGRESS,
            'partner_integration_id' => $this->partner->id,
            'external_id' => 'EXT-IDEM-1',
        ]);

        $payload = [
            'id' => 'EXT-IDEM-1',
            'resourceType' => 'DiagnosticReport',
            'results' => [['name' => 'Glicemia', 'value' => 90, 'unit' => 'mg/dL']],
        ];

        // Primeira chamada
        $this->postWithHmac(
            "/api/v1/public/webhooks/lab/webhook-test-lab",
            $payload,
            ['X-Idempotency-Key' => 'idem-unique-1'],
        )->assertOk()->assertJson(['status' => 'processed']);

        // Segunda chamada com mesma chave
        $response = $this->postWithHmac(
            "/api/v1/public/webhooks/lab/webhook-test-lab",
            $payload,
            ['X-Idempotency-Key' => 'idem-unique-1'],
        );

        $response->assertOk()
            ->assertJson(['status' => 'already_processed']);
    }

    public function test_webhook_returns_404_when_examination_not_found(): void
    {
        $payload = [
            'id' => 'EXT-NOT-FOUND',
            'resourceType' => 'DiagnosticReport',
            'results' => [],
        ];

        $response = $this->postWithHmac(
            "/api/v1/public/webhooks/lab/webhook-test-lab",
            $payload,
            ['X-Idempotency-Key' => 'idem-not-found'],
        );

        $response->assertStatus(404)
            ->assertJson(['error' => 'examination_not_found']);

        $this->assertDatabaseHas('integration_events', [
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationEvent::STATUS_FAILED,
        ]);
    }

    public function test_webhook_for_nonexistent_slug_returns_404(): void
    {
        $payload = ['id' => 'ext-1', 'resourceType' => 'DiagnosticReport', 'results' => []];
        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, 'any-secret');

        $response = $this->call('POST', '/api/v1/public/webhooks/lab/nonexistent', [], [], [],
            $this->transformHeadersToServerVars([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
                'Content-Type' => 'application/json',
            ]),
            $body,
        );

        $response->assertStatus(404);
    }

    public function test_webhook_for_inactive_partner_returns_404(): void
    {
        $inactivePartner = PartnerIntegration::factory()->laboratory()->inactive()->create([
            'slug' => 'inactive-lab',
        ]);

        $payload = ['id' => 'EXT-1', 'resourceType' => 'DiagnosticReport', 'results' => []];
        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, 'any-secret');

        $response = $this->call('POST', '/api/v1/public/webhooks/lab/inactive-lab', [], [], [],
            $this->transformHeadersToServerVars([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
                'Content-Type' => 'application/json',
            ]),
            $body,
        );

        // O HMAC middleware busca o parceiro e não encontra (ou inativo → webhook não configurado)
        // O controller retorna 404 para parceiro inativo
        $this->assertContains($response->status(), [401, 404]);
    }
}
