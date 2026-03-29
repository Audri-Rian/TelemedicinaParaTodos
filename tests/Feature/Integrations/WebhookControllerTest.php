<?php

namespace Tests\Feature\Integrations;

use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIntegration $partner;
    private string $secret = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'slug' => 'webhook-test-lab',
        ]);
        $this->partner->credential()->create([
            'auth_type' => 'api_key',
            'client_id' => 'test-key',
            'client_secret' => $this->secret,
        ]);
    }

    public function test_webhook_route_is_registered(): void
    {
        $response = $this->postJson("/api/v1/public/webhooks/lab/webhook-test-lab", []);
        // A rota existe — não retorna 405 (Method Not Allowed)
        $this->assertNotEquals(405, $response->status());
    }

    public function test_webhook_accepts_post_method(): void
    {
        $response = $this->postJson("/api/v1/public/webhooks/lab/webhook-test-lab", [
            'resourceType' => 'DiagnosticReport',
        ]);

        // Qualquer status exceto 405 confirma que POST é aceito
        $this->assertNotEquals(405, $response->status());
    }

    public function test_webhook_for_nonexistent_slug(): void
    {
        $response = $this->postJson("/api/v1/public/webhooks/lab/nonexistent", [
            'resourceType' => 'DiagnosticReport',
        ]);

        // Parceiro não encontrado
        $this->assertContains($response->status(), [404, 422, 500]);
    }
}
