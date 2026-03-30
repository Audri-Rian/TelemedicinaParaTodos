<?php

namespace Tests\Feature\Integrations;

use App\Integrations\Events\ExamResultReceived;
use App\Models\Examination;
use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIntegration $partner;

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
    }

    /**
     * Cria exame sem disparar o ExaminationObserver.
     */
    private function createExamWithoutEvents(array $attributes = []): Examination
    {
        return Examination::withoutEvents(fn () => Examination::factory()->create($attributes));
    }

    public function test_webhook_route_is_registered(): void
    {
        $response = $this->postJson("/api/v1/public/webhooks/lab/webhook-test-lab", []);
        $this->assertNotEquals(405, $response->status());
    }

    public function test_webhook_for_nonexistent_slug_returns_404(): void
    {
        $response = $this->postJson("/api/v1/public/webhooks/lab/nonexistent", [
            'resourceType' => 'DiagnosticReport',
            'id' => 'ext-1',
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'partner_not_found']);
    }

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

        $response = $this->postJson(
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

        // Primeira chamada — processa
        $this->postJson(
            "/api/v1/public/webhooks/lab/webhook-test-lab",
            $payload,
            ['X-Idempotency-Key' => 'idem-unique-1'],
        )->assertOk()->assertJson(['status' => 'processed']);

        // Segunda chamada com mesma chave — já processado
        $response = $this->postJson(
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

        $response = $this->postJson(
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

    public function test_webhook_for_inactive_partner_returns_404(): void
    {
        PartnerIntegration::factory()->laboratory()->inactive()->create([
            'slug' => 'inactive-lab',
        ]);

        $response = $this->postJson("/api/v1/public/webhooks/lab/inactive-lab", [
            'id' => 'EXT-1',
            'resourceType' => 'DiagnosticReport',
            'results' => [],
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'partner_not_found']);
    }
}
