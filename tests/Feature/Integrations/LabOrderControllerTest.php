<?php

namespace Tests\Feature\Integrations;

use App\Models\Examination;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @todo O campo access_token no IntegrationCredential tem cast 'encrypted',
 *       o que impede lookup via WHERE. O AuthenticatePartner middleware
 *       precisa ser refatorado para usar um campo token_hash não-encrypted
 *       para busca, mantendo o token real encrypted. Por enquanto, testes
 *       de autenticação OAuth2 Bearer estão limitados.
 */
class LabOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIntegration $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'slug' => 'test-lab-orders',
        ]);
        $this->partner->credential()->create([
            'auth_type' => 'oauth2_client_credentials',
            'client_id' => 'lab-orders-client',
            'client_secret' => 'secret',
        ]);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson("/api/v1/public/lab/test-lab-orders/orders")
            ->assertStatus(401);
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->getJson(
            "/api/v1/public/lab/test-lab-orders/orders",
            ['Authorization' => 'Bearer wrong-token']
        )->assertStatus(401);
    }

    public function test_lab_order_route_exists(): void
    {
        // Verifica que a rota existe (não é 405)
        $response = $this->getJson("/api/v1/public/lab/test-lab-orders/orders");
        $this->assertNotEquals(405, $response->status());
    }

    public function test_lab_order_endpoint_requires_bearer(): void
    {
        $response = $this->getJson("/api/v1/public/lab/test-lab-orders/orders");
        $response->assertStatus(401)
            ->assertJson(['error' => 'unauthorized']);
    }
}
