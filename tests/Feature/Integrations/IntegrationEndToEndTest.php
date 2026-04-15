<?php

namespace Tests\Feature\Integrations;

use App\Events\ExaminationRequested;
use App\Integrations\Events\ExamResultReceived;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\IntegrationEvent;
use App\Models\IntegrationWebhook;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Teste ponta a ponta: simula o fluxo completo de integração.
 *
 * 1. Médico cria parceiro ativo com capability send_exam_order
 * 2. Sistema solicita exame laboratorial (dispara evento)
 * 3. Stub retorna external_id
 * 4. Parceiro consulta pedidos pendentes via API
 * 5. Parceiro envia resultado via webhook (com HMAC)
 * 6. Exame fica completo com source = integration
 */
class IntegrationEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_exam_order_to_result_flow(): void
    {
        // ── 1. Médico cria parceiro via wizard ──────────────────

        $doctor = Doctor::factory()->create();
        $user = $doctor->user;

        $response = $this->actingAs($user)->post('/doctor/integrations/connect', [
            'partner_name' => 'Lab E2E Test',
            'partner_slug' => 'lab-e2e-test',
            'partner_type' => 'laboratory',
            'integration_mode' => 'full',
            'base_url' => 'https://api.e2e-lab.com/fhir/r4',
            'fhir_version' => 'R4',
            'auth_method' => 'oauth2',
            'client_id' => 'e2e-client',
            'client_secret' => 'e2e-secret',
            'perm_send_orders' => true,
            'perm_receive_results' => true,
            'perm_webhook' => true,
        ]);

        $response->assertRedirect();

        $partner = PartnerIntegration::where('slug', 'lab-e2e-test')->first();
        $this->assertNotNull($partner, 'Parceiro deveria ter sido criado pelo wizard');
        $this->assertEquals(PartnerIntegration::STATUS_ACTIVE, $partner->status);
        $this->assertTrue($partner->hasCapability('send_exam_order'));

        // ── 2. Sistema solicita exame laboratorial ──────────────

        $examination = Examination::withoutEvents(fn () => Examination::factory()->create([
            'type' => Examination::TYPE_LAB,
            'name' => 'Hemograma Completo',
            'status' => Examination::STATUS_REQUESTED,
            'doctor_id' => $doctor->id,
        ]));

        ExaminationRequested::dispatch($examination);

        // ── 3. Verificar que stub retornou external_id ──────────

        $examination->refresh();

        $this->assertNotNull($examination->external_id, 'Stub deveria ter definido external_id');
        $this->assertStringStartsWith('stub-', $examination->external_id);
        $this->assertEquals(Examination::STATUS_IN_PROGRESS, $examination->status);
        $this->assertEquals($partner->id, $examination->partner_integration_id);

        $this->assertDatabaseHas('integration_events', [
            'partner_integration_id' => $partner->id,
            'direction' => IntegrationEvent::DIRECTION_OUTBOUND,
            'event_type' => IntegrationEvent::EVENT_EXAM_ORDER_SENT,
            'status' => IntegrationEvent::STATUS_SUCCESS,
        ]);

        // ── 4. Parceiro consulta pedidos via API ────────────────

        $rawToken = Str::random(64);
        $partner->credential()->delete();
        $partner->credential()->create([
            'auth_type' => 'oauth2_client_credentials',
            'client_id' => 'e2e-client',
            'client_secret' => bcrypt('e2e-secret'),
            'access_token' => hash('sha256', $rawToken),
            'token_expires_at' => now()->addHour(),
            'scopes' => ['lab:read'],
        ]);

        $ordersResponse = $this->getJson(
            "/api/v1/public/lab/lab-e2e-test/orders",
            ['Authorization' => "Bearer {$rawToken}"]
        );

        $ordersResponse->assertOk()
            ->assertJsonPath('resourceType', 'Bundle')
            ->assertJsonPath('total', 1);

        $this->assertEquals(
            $examination->external_id,
            $ordersResponse->json('entry.0.resource.id')
        );

        // ── 5. Parceiro envia resultado via webhook (com HMAC) ──

        // Configurar webhook com secret HMAC
        $webhookSecret = 'e2e-hmac-secret';
        $partner->webhooks()->create([
            'url' => 'https://api.e2e-lab.com/webhook',
            'secret' => $webhookSecret,
            'events' => ['exam_result_received'],
            'status' => IntegrationWebhook::STATUS_ACTIVE,
        ]);

        // Fake ExamResultReceived para evitar que o ProcessExamResult listener dispare
        Event::fake([ExamResultReceived::class]);

        $webhookPayload = [
            'id' => $examination->external_id,
            'resourceType' => 'DiagnosticReport',
            'results' => [
                ['name' => 'Hemoglobina', 'value' => 14.2, 'unit' => 'g/dL', 'status' => 'normal'],
                ['name' => 'Leucócitos', 'value' => 7500, 'unit' => '/mm³', 'status' => 'normal'],
            ],
        ];

        $body = json_encode($webhookPayload);
        $timestamp = (string) time();
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $webhookSecret);

        $webhookResponse = $this->call('POST', '/api/v1/public/webhooks/lab/lab-e2e-test', [], [], [],
            $this->transformHeadersToServerVars([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
                'X-Idempotency-Key' => 'e2e-result-1',
                'Content-Type' => 'application/json',
            ]),
            $body,
        );

        $webhookResponse->assertOk()
            ->assertJson(['status' => 'processed']);

        // ── 6. Verificar estado final ───────────────────────────

        $examination->refresh();

        $this->assertEquals(Examination::STATUS_COMPLETED, $examination->status);
        $this->assertEquals(Examination::SOURCE_INTEGRATION, $examination->source);
        $this->assertNotNull($examination->completed_at);
        $this->assertNotNull($examination->received_from_partner_at);
        $this->assertIsArray($examination->results);
        $this->assertCount(2, $examination->results);

        $this->assertDatabaseHas('integration_events', [
            'partner_integration_id' => $partner->id,
            'direction' => IntegrationEvent::DIRECTION_INBOUND,
            'event_type' => IntegrationEvent::EVENT_EXAM_RESULT_RECEIVED,
            'status' => IntegrationEvent::STATUS_SUCCESS,
        ]);

        $partner->refresh();
        $this->assertNotNull($partner->last_sync_at);

        $this->assertEquals(2, IntegrationEvent::where('partner_integration_id', $partner->id)->count());
    }

    public function test_exam_without_lab_partner_follows_manual_flow(): void
    {
        PartnerIntegration::factory()->laboratory()->inactive()->create();

        $examination = Examination::withoutEvents(fn () => Examination::factory()->create([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ]));

        ExaminationRequested::dispatch($examination);

        $examination->refresh();

        $this->assertEquals(Examination::STATUS_REQUESTED, $examination->status);
        $this->assertNull($examination->external_id);
        $this->assertNull($examination->partner_integration_id);
        $this->assertDatabaseCount('integration_events', 0);
    }
}
