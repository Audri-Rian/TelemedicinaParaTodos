<?php

namespace Tests\Feature\Integrations;

use App\Models\IntegrationCredential;
use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PartnerHealthControllerTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIntegration $partner;
    private string $rawToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'slug' => 'health-test-lab',
            'type' => PartnerIntegration::TYPE_LABORATORY,
            'capabilities' => ['send_exam_order', 'receive_exam_result', 'webhook_result'],
            'last_sync_at' => now()->subHour(),
        ]);

        $this->rawToken = Str::random(64);

        $this->partner->credential()->create([
            'auth_type' => IntegrationCredential::AUTH_OAUTH2_CLIENT_CREDENTIALS,
            'client_id' => 'health-client',
            'client_secret' => bcrypt('secret'),
            'access_token' => hash('sha256', $this->rawToken),
            'token_expires_at' => now()->addHour(),
            'scopes' => ['lab:read', 'lab:write'],
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->rawToken];
    }

    // ─── Autenticação ────────────────────────────────────────────

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson("/api/v1/public/health/{$this->partner->slug}")
            ->assertStatus(401);
    }

    public function test_invalid_bearer_token_returns_401(): void
    {
        $this->getJson(
            "/api/v1/public/health/{$this->partner->slug}",
            ['Authorization' => 'Bearer not-a-real-token']
        )->assertStatus(401);
    }

    // ─── Fluxo de sucesso ────────────────────────────────────────

    public function test_active_partner_returns_ok_status(): void
    {
        IntegrationEvent::factory()->successful()->create([
            'partner_integration_id' => $this->partner->id,
            'created_at' => now()->subMinutes(10),
        ]);

        $response = $this->getJson(
            "/api/v1/public/health/health-test-lab",
            $this->authHeaders(),
        );

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'partner',
                'type',
                'capabilities',
                'last_event',
                'last_sync',
            ])
            ->assertJson([
                'status' => 'ok',
                'partner' => 'health-test-lab',
                'type' => PartnerIntegration::TYPE_LABORATORY,
                'capabilities' => ['send_exam_order', 'receive_exam_result', 'webhook_result'],
            ]);

        $this->assertNotNull($response->json('last_event'));
        $this->assertNotNull($response->json('last_sync'));
    }

    public function test_partner_without_events_returns_null_last_event(): void
    {
        $response = $this->getJson(
            "/api/v1/public/health/health-test-lab",
            $this->authHeaders(),
        );

        $response->assertOk()
            ->assertJson(['last_event' => null]);
    }

    public function test_partner_without_last_sync_returns_null_last_sync(): void
    {
        $this->partner->update(['last_sync_at' => null]);

        $response = $this->getJson(
            "/api/v1/public/health/health-test-lab",
            $this->authHeaders(),
        );

        $response->assertOk()
            ->assertJson(['last_sync' => null]);
    }

    public function test_timestamps_are_in_iso8601_format(): void
    {
        IntegrationEvent::factory()->successful()->create([
            'partner_integration_id' => $this->partner->id,
        ]);

        $response = $this->getJson(
            "/api/v1/public/health/health-test-lab",
            $this->authHeaders(),
        );

        $response->assertOk();

        // ISO 8601 completo: aceita 'Z' (UTC), '+HH:MM' ou '-HH:MM' como sufixo
        // de timezone. Antes o regex só validava a parte local e deixava timestamps
        // sem tz passar.
        $iso8601 = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/';
        $this->assertMatchesRegularExpression($iso8601, $response->json('last_event'));
        $this->assertMatchesRegularExpression($iso8601, $response->json('last_sync'));
    }

    // ─── Edge cases ──────────────────────────────────────────────

    public function test_returns_404_for_nonexistent_slug(): void
    {
        // Middleware partner.auth autentica por token (vinculado ao partner real),
        // mas o controller faz lookup pelo slug da URL. Slug inexistente → 404.
        $response = $this->getJson(
            "/api/v1/public/health/nonexistent-slug",
            $this->authHeaders(),
        );

        $response->assertStatus(404)
            ->assertJson(['error' => 'partner_not_found']);
    }

    public function test_inactive_partner_cannot_authenticate(): void
    {
        // Parceiro inativo não passa pelo middleware partner.auth (retorna 403).
        $this->partner->update(['status' => PartnerIntegration::STATUS_INACTIVE]);

        $response = $this->getJson(
            "/api/v1/public/health/health-test-lab",
            $this->authHeaders(),
        );

        $response->assertStatus(403);
    }

    public function test_returns_latest_event_timestamp_ordered_correctly(): void
    {
        IntegrationEvent::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'created_at' => now()->subDays(2),
        ]);
        IntegrationEvent::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'created_at' => now()->subMinutes(5),
        ]);
        IntegrationEvent::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'created_at' => now()->subHour(),
        ]);

        $response = $this->getJson(
            "/api/v1/public/health/health-test-lab",
            $this->authHeaders(),
        );

        $response->assertOk();
        $lastEvent = $response->json('last_event');

        // Deve ser o mais recente (5 minutos atrás), não o mais antigo.
        // Validamos primeiro que strtotime não retornou false — sem isso, um
        // timestamp malformado passaria silenciosamente ($timestamp=false vira 0
        // em contexto numérico, que é > time()-600 em épocas futuras…).
        $timestamp = strtotime($lastEvent);
        $this->assertNotFalse($timestamp, "last_event não pôde ser parseado como timestamp: {$lastEvent}");
        $this->assertGreaterThan(time() - 600, $timestamp);
    }
}
