<?php

namespace Tests\Feature\Integrations;

use App\Models\Consent;
use App\Models\Examination;
use App\Models\IntegrationCredential;
use App\Models\PartnerIntegration;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnforcePatientConsentTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIntegration $partner;
    private string $rawToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'slug' => 'consent-test-lab',
        ]);

        $this->rawToken = Str::random(64);

        $this->partner->credential()->create([
            'auth_type' => IntegrationCredential::AUTH_OAUTH2_CLIENT_CREDENTIALS,
            'client_id' => 'consent-client',
            'client_secret' => bcrypt('secret'),
            'access_token' => hash('sha256', $this->rawToken),
            'token_expires_at' => now()->addHour(),
            'scopes' => ['lab:read'],
        ]);
    }

    public function test_blocks_access_when_patient_has_no_consent(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->getJson(
            "/api/v1/public/lab/consent-test-lab/orders?patient_id={$patient->id}",
            ['Authorization' => "Bearer {$this->rawToken}"]
        );

        $response->assertStatus(403)
            ->assertJson(['error' => 'consent_required']);
    }

    public function test_allows_access_when_patient_has_active_consent(): void
    {
        $patient = Patient::factory()->create();

        Consent::create([
            'user_id' => $patient->user->id,
            'type' => Consent::TYPE_DATA_SHARING_LAB,
            'granted' => true,
            'granted_at' => now(),
            'version' => '1.0',
        ]);

        $response = $this->getJson(
            "/api/v1/public/lab/consent-test-lab/orders?patient_id={$patient->id}",
            ['Authorization' => "Bearer {$this->rawToken}"]
        );

        $response->assertOk();
    }

    public function test_passes_through_when_no_patient_id_in_request(): void
    {
        $response = $this->getJson(
            "/api/v1/public/lab/consent-test-lab/orders",
            ['Authorization' => "Bearer {$this->rawToken}"]
        );

        $response->assertOk();
    }

    public function test_blocks_when_consent_was_revoked(): void
    {
        $patient = Patient::factory()->create();

        Consent::create([
            'user_id' => $patient->user->id,
            'type' => Consent::TYPE_DATA_SHARING_LAB,
            'granted' => false,
            'granted_at' => now()->subDay(),
            'revoked_at' => now(),
            'version' => '1.0',
        ]);

        $response = $this->getJson(
            "/api/v1/public/lab/consent-test-lab/orders?patient_id={$patient->id}",
            ['Authorization' => "Bearer {$this->rawToken}"]
        );

        $response->assertStatus(403)
            ->assertJson(['error' => 'consent_required']);
    }
}
