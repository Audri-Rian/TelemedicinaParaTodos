<?php

namespace Tests\Feature\Integrations;

use App\Models\Doctor;
use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorIntegrationsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = Doctor::factory()->create();
        $this->user = $this->doctor->user;
    }

    public function test_hub_returns_real_stats(): void
    {
        $partner = PartnerIntegration::factory()->laboratory()->active()->create();
        IntegrationEvent::factory()->count(3)->failed()->create([
            'partner_integration_id' => $partner->id,
        ]);

        $response = $this->actingAs($this->user)->get('/doctor/integrations');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Doctor/Integrations/Hub')
                ->has('stats')
                ->where('stats.activeIntegrations', 1)
                ->where('stats.errors24h', 3)
            );
    }

    public function test_partners_page_returns_partner_list(): void
    {
        $partner = PartnerIntegration::factory()->laboratory()->active()->create();
        IntegrationEvent::factory()->count(2)->outbound()->create([
            'partner_integration_id' => $partner->id,
        ]);

        $response = $this->actingAs($this->user)->get('/doctor/integrations/partners');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Doctor/Integrations/Partners')
                ->has('partners', 1)
                ->has('criticalEvents')
            );
    }

    public function test_connect_wizard_stores_partner(): void
    {
        $response = $this->actingAs($this->user)->post('/doctor/integrations/connect', [
            'partner_name' => 'Lab Test',
            'partner_slug' => 'lab-test-unique',
            'partner_type' => 'laboratory',
            'integration_mode' => 'full',
            'base_url' => 'https://api.labtest.com/fhir/r4',
            'fhir_version' => 'R4',
            'auth_method' => 'api_key',
            'client_id' => 'test-key-123',
            'perm_send_orders' => true,
            'perm_receive_results' => true,
            'perm_webhook' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('partner_integrations', [
            'name' => 'Lab Test',
            'slug' => 'lab-test-unique',
            'status' => PartnerIntegration::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('integration_credentials', [
            'auth_type' => 'api_key',
            'client_id' => 'test-key-123',
        ]);
    }

    public function test_connect_wizard_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post('/doctor/integrations/connect', []);

        $response->assertSessionHasErrors(['partner_name', 'partner_slug', 'integration_mode']);
    }

    public function test_connect_wizard_rejects_duplicate_slug(): void
    {
        PartnerIntegration::factory()->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($this->user)->post('/doctor/integrations/connect', [
            'partner_name' => 'Duplicate',
            'partner_slug' => 'existing-slug',
            'integration_mode' => 'receive_only',
        ]);

        $response->assertSessionHasErrors(['partner_slug']);
    }

    public function test_show_returns_partner_detail(): void
    {
        $partner = PartnerIntegration::factory()->active()->withCredential()->create();

        $response = $this->actingAs($this->user)->get("/doctor/integrations/{$partner->id}");

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Doctor/Integrations/Show')
                ->has('partner')
                ->has('events')
                ->has('stats')
            );
    }

    public function test_sync_returns_json(): void
    {
        $partner = PartnerIntegration::factory()->laboratory()->active()->create();

        $response = $this->actingAs($this->user)->postJson("/doctor/integrations/{$partner->id}/sync");

        $response->assertOk()
            ->assertJsonStructure(['message', 'received']);
    }

    public function test_sync_rejects_inactive_partner(): void
    {
        $partner = PartnerIntegration::factory()->inactive()->create();

        $response = $this->actingAs($this->user)->postJson("/doctor/integrations/{$partner->id}/sync");

        $response->assertStatus(422);
    }

    public function test_unauthenticated_access_redirects(): void
    {
        $response = $this->get('/doctor/integrations');

        $response->assertRedirect();
    }
}
