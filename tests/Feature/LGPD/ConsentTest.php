<?php

namespace Tests\Feature\LGPD;

use App\Models\Consent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // --- Grant consent ---

    public function test_user_can_grant_consent(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lgpd.consents.grant'), [
                'type' => Consent::TYPE_TELEMEDICINE,
                'version' => '1.0',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Consentimento concedido com sucesso']);

        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'type' => Consent::TYPE_TELEMEDICINE,
            'granted' => true,
        ]);
    }

    public function test_user_can_grant_data_processing_consent(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lgpd.consents.grant'), [
                'type' => Consent::TYPE_DATA_PROCESSING,
                'version' => '1.0',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'type' => Consent::TYPE_DATA_PROCESSING,
            'granted' => true,
        ]);
    }

    public function test_grant_consent_rejects_invalid_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lgpd.consents.grant'), [
                'type' => 'invalid_type',
            ]);

        $response->assertUnprocessable();
    }

    public function test_grant_consent_requires_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lgpd.consents.grant'), []);

        $response->assertUnprocessable();
    }

    // --- Revoke consent ---

    public function test_user_can_revoke_active_consent(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('lgpd.consents.grant'), [
                'type' => Consent::TYPE_TELEMEDICINE,
                'version' => '1.0',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('lgpd.consents.revoke'), [
                'type' => Consent::TYPE_TELEMEDICINE,
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Consentimento revogado com sucesso']);

        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'type' => Consent::TYPE_TELEMEDICINE,
            'granted' => false,
        ]);
    }

    public function test_revoke_consent_rejects_invalid_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lgpd.consents.revoke'), [
                'type' => 'fake_type',
            ]);

        $response->assertUnprocessable();
    }

    // --- Check consent ---

    public function test_user_can_check_active_consent(): void
    {
        Consent::create([
            'user_id' => $this->user->id,
            'type' => Consent::TYPE_TELEMEDICINE,
            'granted' => true,
            'version' => '1.0',
            'granted_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('lgpd.consents.check', ['type' => Consent::TYPE_TELEMEDICINE]));

        $response->assertOk();
        $response->assertJsonFragment(['has_consent' => true]);
    }

    public function test_check_returns_false_when_no_consent(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('lgpd.consents.check', ['type' => Consent::TYPE_TELEMEDICINE]));

        $response->assertOk();
        $response->assertJsonFragment(['has_consent' => false]);
    }

    public function test_check_returns_false_for_revoked_consent(): void
    {
        Consent::create([
            'user_id' => $this->user->id,
            'type' => Consent::TYPE_TELEMEDICINE,
            'granted' => false,
            'version' => '1.0',
            'revoked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('lgpd.consents.check', ['type' => Consent::TYPE_TELEMEDICINE]));

        $response->assertOk();
        $response->assertJsonFragment(['has_consent' => false]);
    }

    // --- Guest access ---

    public function test_guest_cannot_grant_consent(): void
    {
        $response = $this->postJson(route('lgpd.consents.grant'), [
            'type' => Consent::TYPE_TELEMEDICINE,
        ]);

        $response->assertUnauthorized();
    }

    public function test_guest_cannot_revoke_consent(): void
    {
        $response = $this->postJson(route('lgpd.consents.revoke'), [
            'type' => Consent::TYPE_TELEMEDICINE,
        ]);

        $response->assertUnauthorized();
    }

    public function test_guest_cannot_check_consent(): void
    {
        $response = $this->getJson(route('lgpd.consents.check', ['type' => Consent::TYPE_TELEMEDICINE]));

        $response->assertUnauthorized();
    }
}
