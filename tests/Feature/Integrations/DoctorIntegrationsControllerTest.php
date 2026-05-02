<?php

namespace Tests\Feature\Integrations;

use App\Jobs\SyncPartnerExamResultsJob;
use App\Models\Doctor;
use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
        $this->doctor->partnerIntegrations()->attach($partner->id, [
            'integration_mode' => 'full',
            'perm_send_orders' => true,
            'perm_receive_results' => true,
            'perm_webhook' => true,
            'perm_patient_data' => false,
            'connected_by' => $this->user->id,
            'connected_at' => now(),
        ]);

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
        $this->doctor->partnerIntegrations()->attach($partner->id, [
            'integration_mode' => 'full',
            'perm_send_orders' => true,
            'perm_receive_results' => true,
            'perm_webhook' => true,
            'perm_patient_data' => false,
            'connected_by' => $this->user->id,
            'connected_at' => now(),
        ]);

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
        ]);
        $this->assertDatabaseHas('doctor_partner_integrations', [
            'doctor_id' => $this->doctor->id,
            'partner_integration_id' => $this->getPartnerIdBySlug('lab-test-unique'),
            'integration_mode' => 'full',
        ]);
    }

    public function test_connect_wizard_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post('/doctor/integrations/connect', []);

        $response->assertSessionHasErrors(['partner_name', 'partner_slug', 'integration_mode']);
    }

    public function test_connect_wizard_rejects_private_base_url(): void
    {
        $response = $this->actingAs($this->user)->post('/doctor/integrations/connect', [
            'partner_name' => 'Lab Privado',
            'partner_slug' => 'lab-privado',
            'integration_mode' => 'full',
            'base_url' => 'http://127.0.0.1:8080/fhir',
            'auth_method' => 'api_key',
            'client_id' => 'key-123',
        ]);

        $response->assertSessionHasErrors(['base_url']);
    }

    public function test_connect_wizard_allows_existing_slug_and_links_doctor(): void
    {
        $existingPartner = PartnerIntegration::factory()->create([
            'slug' => 'existing-slug',
            'name' => 'Original Partner',
            'base_url' => 'https://api.original.com/fhir/r4',
        ]);

        $response = $this->actingAs($this->user)->post('/doctor/integrations/connect', [
            'partner_name' => 'Duplicate',
            'partner_slug' => 'existing-slug',
            'integration_mode' => 'receive_only',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('partner_integrations', 1);
        $this->assertDatabaseHas('doctor_partner_integrations', [
            'doctor_id' => $this->doctor->id,
            'partner_integration_id' => $existingPartner->id,
            'integration_mode' => 'receive_only',
        ]);
        $existingPartner->refresh();
        $this->assertSame('Original Partner', $existingPartner->name);
        $this->assertSame('https://api.original.com/fhir/r4', $existingPartner->base_url);
    }

    public function test_show_returns_partner_detail(): void
    {
        $partner = PartnerIntegration::factory()->active()->withCredential()->create();
        $this->doctor->partnerIntegrations()->attach($partner->id, [
            'integration_mode' => 'full',
            'perm_send_orders' => true,
            'perm_receive_results' => true,
            'perm_webhook' => true,
            'perm_patient_data' => false,
            'connected_by' => $this->user->id,
            'connected_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get("/doctor/integrations/{$partner->id}");

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Doctor/Integrations/Show')
                ->has('partner')
                ->has('events')
                ->has('stats')
            );
    }

    public function test_sync_redirects_with_flash_message(): void
    {
        Queue::fake();

        $partner = PartnerIntegration::factory()->laboratory()->active()->create();
        $this->doctor->partnerIntegrations()->attach($partner->id, [
            'integration_mode' => 'full',
            'perm_send_orders' => true,
            'perm_receive_results' => true,
            'perm_webhook' => true,
            'perm_patient_data' => false,
            'connected_by' => $this->user->id,
            'connected_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->post("/doctor/integrations/{$partner->id}/sync");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Queue::assertPushed(SyncPartnerExamResultsJob::class);
    }

    public function test_sync_rejects_inactive_partner(): void
    {
        $partner = PartnerIntegration::factory()->inactive()->create();
        $this->doctor->partnerIntegrations()->attach($partner->id, [
            'integration_mode' => 'full',
            'perm_send_orders' => true,
            'perm_receive_results' => true,
            'perm_webhook' => true,
            'perm_patient_data' => false,
            'connected_by' => $this->user->id,
            'connected_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->post("/doctor/integrations/{$partner->id}/sync");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_unauthenticated_access_redirects(): void
    {
        $response = $this->get('/doctor/integrations');

        $response->assertRedirect();
    }

    public function test_doctor_cannot_view_partner_from_another_doctor(): void
    {
        $otherDoctor = Doctor::factory()->create();
        $partner = PartnerIntegration::factory()->active()->create();

        $otherDoctor->partnerIntegrations()->attach($partner->id, [
            'integration_mode' => 'full',
            'perm_send_orders' => true,
            'perm_receive_results' => true,
            'perm_webhook' => true,
            'perm_patient_data' => false,
            'connected_by' => $otherDoctor->user->id,
            'connected_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get("/doctor/integrations/{$partner->id}");

        $response->assertNotFound();
    }

    public function test_doctor_cannot_sync_partner_from_another_doctor(): void
    {
        Queue::fake();

        $otherDoctor = Doctor::factory()->create();
        $partner = PartnerIntegration::factory()->active()->create();

        $otherDoctor->partnerIntegrations()->attach($partner->id, [
            'integration_mode' => 'full',
            'perm_send_orders' => true,
            'perm_receive_results' => true,
            'perm_webhook' => true,
            'perm_patient_data' => false,
            'connected_by' => $otherDoctor->user->id,
            'connected_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->post("/doctor/integrations/{$partner->id}/sync");

        $response->assertNotFound();
        Queue::assertNothingPushed();
    }

    private function getPartnerIdBySlug(string $slug): string
    {
        return PartnerIntegration::query()
            ->where('slug', $slug)
            ->valueOrFail('id');
    }
}
