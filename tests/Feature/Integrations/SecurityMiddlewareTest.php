<?php

namespace Tests\Feature\Integrations;

use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Testes de segurança dos middlewares da API pública.
 *
 * @todo Testes de token válido precisam de refatoração no AuthenticatePartner
 *       para suportar lookup em campo encrypted (access_token).
 */
class SecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_bearer_returns_401(): void
    {
        PartnerIntegration::factory()->laboratory()->active()->create(['slug' => 'sec-lab']);

        $this->getJson("/api/v1/public/lab/sec-lab/orders")
            ->assertStatus(401)
            ->assertJson(['error' => 'unauthorized']);
    }

    public function test_health_endpoint_requires_auth(): void
    {
        PartnerIntegration::factory()->laboratory()->active()->create(['slug' => 'sec-lab']);

        $this->getJson("/api/v1/public/health/sec-lab")
            ->assertStatus(401);
    }

    public function test_invalid_bearer_returns_401(): void
    {
        PartnerIntegration::factory()->laboratory()->active()->create(['slug' => 'sec-lab']);

        $this->getJson(
            "/api/v1/public/lab/sec-lab/orders",
            ['Authorization' => 'Bearer totally-invalid-token']
        )->assertStatus(401);
    }

    public function test_oauth_token_endpoint_accessible(): void
    {
        // OAuth token endpoint não precisa de Bearer (é onde você pega o token)
        $response = $this->postJson('/api/v1/public/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'nonexistent',
            'client_secret' => 'wrong',
        ]);

        // Deve retornar 401 (invalid_client), não 404
        $response->assertStatus(401);
    }
}
