<?php

namespace Tests\Unit\Models;

use App\Models\IntegrationCredential;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IntegrationCredentialTest extends TestCase
{
    use RefreshDatabase;

    private function makeCredential(array $attrs = []): IntegrationCredential
    {
        $partner = PartnerIntegration::factory()->laboratory()->create();

        return IntegrationCredential::create(array_merge([
            'partner_integration_id' => $partner->id,
            'auth_type' => IntegrationCredential::AUTH_OAUTH2_CLIENT_CREDENTIALS,
            'client_id' => 'client-123',
            'client_secret' => 'secret-value-plain',
            'access_token' => 'access-token-value',
            'refresh_token' => 'refresh-token-value',
            'certificate_password' => 'cert-password-plain',
        ], $attrs));
    }

    // ─── Encryption ───────────────────────────────────────────────

    public function test_client_secret_is_encrypted_at_rest(): void
    {
        $credential = $this->makeCredential(['client_secret' => 'my-secret-xyz']);

        // Leitura via Eloquent é decifrada transparentemente
        $this->assertEquals('my-secret-xyz', $credential->fresh()->client_secret);

        // Leitura via raw query deve retornar valor cifrado (não plaintext)
        $raw = DB::table('integration_credentials')->where('id', $credential->id)->value('client_secret');
        $this->assertNotEquals('my-secret-xyz', $raw);
        $this->assertNotEmpty($raw);
    }

    public function test_access_token_is_encrypted_at_rest(): void
    {
        $credential = $this->makeCredential(['access_token' => 'token-plaintext-123']);

        $this->assertEquals('token-plaintext-123', $credential->fresh()->access_token);

        $raw = DB::table('integration_credentials')->where('id', $credential->id)->value('access_token');
        $this->assertNotEquals('token-plaintext-123', $raw);
    }

    public function test_refresh_token_is_encrypted_at_rest(): void
    {
        $credential = $this->makeCredential(['refresh_token' => 'refresh-plaintext-456']);

        $this->assertEquals('refresh-plaintext-456', $credential->fresh()->refresh_token);

        $raw = DB::table('integration_credentials')->where('id', $credential->id)->value('refresh_token');
        $this->assertNotEquals('refresh-plaintext-456', $raw);
    }

    public function test_certificate_password_is_encrypted_at_rest(): void
    {
        $credential = $this->makeCredential(['certificate_password' => 'p@ssw0rd!']);

        $this->assertEquals('p@ssw0rd!', $credential->fresh()->certificate_password);

        $raw = DB::table('integration_credentials')->where('id', $credential->id)->value('certificate_password');
        $this->assertNotEquals('p@ssw0rd!', $raw);
    }

    // ─── Hidden attributes ───────────────────────────────────────

    public function test_sensitive_fields_are_hidden_in_array_output(): void
    {
        $credential = $this->makeCredential();

        $array = $credential->toArray();

        $this->assertArrayNotHasKey('client_secret', $array);
        $this->assertArrayNotHasKey('access_token', $array);
        $this->assertArrayNotHasKey('refresh_token', $array);
        $this->assertArrayNotHasKey('certificate_password', $array);
    }

    public function test_sensitive_fields_are_hidden_in_json_output(): void
    {
        $credential = $this->makeCredential();

        $json = $credential->toJson();

        $this->assertStringNotContainsString('secret-value-plain', $json);
        $this->assertStringNotContainsString('access-token-value', $json);
        $this->assertStringNotContainsString('refresh-token-value', $json);
        $this->assertStringNotContainsString('cert-password-plain', $json);
    }

    // ─── isTokenExpired ──────────────────────────────────────────

    public function test_is_token_expired_returns_true_when_past(): void
    {
        $credential = $this->makeCredential(['token_expires_at' => now()->subMinute()]);

        $this->assertTrue($credential->isTokenExpired());
    }

    public function test_is_token_expired_returns_false_when_future(): void
    {
        $credential = $this->makeCredential(['token_expires_at' => now()->addHour()]);

        $this->assertFalse($credential->isTokenExpired());
    }

    public function test_is_token_expired_returns_false_when_null(): void
    {
        $credential = $this->makeCredential(['token_expires_at' => null]);

        $this->assertFalse($credential->isTokenExpired());
    }

    // ─── isTokenExpiringSoon ─────────────────────────────────────

    public function test_is_token_expiring_soon_returns_true_within_threshold(): void
    {
        $credential = $this->makeCredential(['token_expires_at' => now()->addMinutes(3)]);

        $this->assertTrue($credential->isTokenExpiringSoon(5));
    }

    public function test_is_token_expiring_soon_returns_false_outside_threshold(): void
    {
        $credential = $this->makeCredential(['token_expires_at' => now()->addMinutes(10)]);

        $this->assertFalse($credential->isTokenExpiringSoon(5));
    }

    public function test_is_token_expiring_soon_returns_false_when_null(): void
    {
        $credential = $this->makeCredential(['token_expires_at' => null]);

        $this->assertFalse($credential->isTokenExpiringSoon());
    }

    public function test_is_token_expiring_soon_custom_threshold(): void
    {
        $credential = $this->makeCredential(['token_expires_at' => now()->addMinutes(25)]);

        $this->assertFalse($credential->isTokenExpiringSoon(10));
        $this->assertTrue($credential->isTokenExpiringSoon(30));
    }

    // ─── Auth type constants ─────────────────────────────────────

    public function test_auth_type_constants_exist(): void
    {
        $this->assertEquals('api_key', IntegrationCredential::AUTH_API_KEY);
        $this->assertEquals('oauth2_client_credentials', IntegrationCredential::AUTH_OAUTH2_CLIENT_CREDENTIALS);
        $this->assertEquals('oauth2_authorization_code', IntegrationCredential::AUTH_OAUTH2_AUTHORIZATION_CODE);
        $this->assertEquals('certificate', IntegrationCredential::AUTH_CERTIFICATE);
        $this->assertEquals('basic_auth', IntegrationCredential::AUTH_BASIC);
        $this->assertEquals('bearer_token', IntegrationCredential::AUTH_BEARER);
    }

    // ─── Relationship ────────────────────────────────────────────

    public function test_belongs_to_partner_integration(): void
    {
        $credential = $this->makeCredential();

        $this->assertInstanceOf(PartnerIntegration::class, $credential->partnerIntegration);
        $this->assertEquals($credential->partner_integration_id, $credential->partnerIntegration->id);
    }

    // ─── Scopes cast ─────────────────────────────────────────────

    public function test_scopes_are_cast_to_array(): void
    {
        $credential = $this->makeCredential(['scopes' => ['lab:read', 'lab:write']]);

        $fresh = $credential->fresh();
        $this->assertIsArray($fresh->scopes);
        $this->assertEquals(['lab:read', 'lab:write'], $fresh->scopes);
    }
}
