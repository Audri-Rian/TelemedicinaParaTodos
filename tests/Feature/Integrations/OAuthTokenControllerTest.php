<?php

namespace Tests\Feature\Integrations;

use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIntegration $partner;
    private string $clientId = 'test-oauth-client';
    private string $clientSecret = 'test-oauth-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->partner = PartnerIntegration::factory()->laboratory()->active()->create();
        $this->partner->credential()->create([
            'auth_type' => 'oauth2_client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
    }

    public function test_valid_credentials_issue_token(): void
    {
        $response = $this->postJson('/api/v1/public/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'scope'])
            ->assertJson(['token_type' => 'Bearer', 'expires_in' => 3600]);
    }

    public function test_invalid_client_id_returns_401(): void
    {
        $this->postJson('/api/v1/public/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'wrong-client',
            'client_secret' => $this->clientSecret,
        ])->assertStatus(401)->assertJson(['error' => 'invalid_client']);
    }

    public function test_invalid_secret_returns_401(): void
    {
        $this->postJson('/api/v1/public/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => 'wrong-secret',
        ])->assertStatus(401)->assertJson(['error' => 'invalid_client']);
    }

    public function test_inactive_partner_returns_403(): void
    {
        $inactive = PartnerIntegration::factory()->inactive()->create();
        $inactive->credential()->create([
            'auth_type' => 'oauth2_client_credentials',
            'client_id' => 'inactive-client',
            'client_secret' => 'inactive-secret',
        ]);

        $this->postJson('/api/v1/public/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'inactive-client',
            'client_secret' => 'inactive-secret',
        ])->assertStatus(403);
    }

    public function test_scopes_granted_by_partner_type(): void
    {
        $response = $this->postJson('/api/v1/public/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $response->assertOk();
        $this->assertStringContainsString('lab:read', $response->json('scope'));
    }

    public function test_invalid_scope_returns_400(): void
    {
        $this->postJson('/api/v1/public/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'lab:read pharmacy:write',
        ])->assertStatus(400)->assertJson(['error' => 'invalid_scope']);
    }

    public function test_valid_specific_scope(): void
    {
        $this->postJson('/api/v1/public/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'lab:read',
        ])->assertOk()->assertJson(['scope' => 'lab:read']);
    }

    public function test_token_is_64_hex_chars(): void
    {
        $response = $this->postJson('/api/v1/public/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $response->assertOk();
        $token = $response->json('access_token');
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }
}
