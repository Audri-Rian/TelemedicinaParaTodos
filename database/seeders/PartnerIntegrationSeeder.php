<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\FhirResourceMapping;
use App\Models\IntegrationEvent;
use App\Models\IntegrationWebhook;
use App\Models\PartnerIntegration;
use Illuminate\Database\Seeder;

class PartnerIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::query()->with('user')->orderBy('created_at')->get()->values();
        $hermesDoctor = $doctors->get(0);
        $fleuryDoctor = $doctors->get(1) ?? $hermesDoctor;
        $pharmacyDoctor = $doctors->get(2) ?? $hermesDoctor;

        // Lab Hermes (ativo, API key)
        $hermes = PartnerIntegration::factory()->laboratory()->active()->create([
            'name' => 'Lab Hermes Pardini',
            'slug' => 'hermes-pardini',
            'base_url' => 'https://api.hermespardini.com.br/fhir/r4',
            'contact_email' => 'integracao@hermespardini.com.br',
            'connected_by' => $hermesDoctor?->user_id,
            'connected_at' => now()->subDays(14),
        ]);
        $hermes->credential()->create([
            'auth_type' => 'api_key',
            'client_id' => 'hermes-test-key-001',
        ]);
        $this->attachDoctor($hermes, $hermesDoctor);
        $this->seedEvents($hermes, $hermesDoctor?->id);
        $this->seedWebhook($hermes);
        $this->seedFhirMappings($hermes);

        // Lab Fleury (ativo, OAuth2) — use para testar OAuth2 token endpoint
        $fleury = PartnerIntegration::factory()->laboratory()->active()->create([
            'name' => 'Fleury',
            'slug' => 'fleury',
            'base_url' => 'https://api.fleury.com.br/fhir/r4',
            'contact_email' => 'tech@fleury.com.br',
            'connected_by' => $fleuryDoctor?->user_id,
            'connected_at' => now()->subDays(10),
        ]);
        $fleury->credential()->create([
            'auth_type' => 'oauth2_client_credentials',
            'client_id' => 'fleury-oauth-client',
            'client_secret' => 'fleury-oauth-secret', // texto plano — encrypted cast protege at rest
        ]);
        $this->attachDoctor($fleury, $fleuryDoctor);
        $this->seedEvents($fleury, $fleuryDoctor?->id);
        $this->seedWebhook($fleury);
        $this->seedFhirMappings($fleury);

        // Farmácia Exemplo (inativa, Bearer)
        $farmacia = PartnerIntegration::factory()->pharmacy()->inactive()->create([
            'name' => 'Farmácia Exemplo',
            'slug' => 'farmacia-exemplo',
            'base_url' => 'https://api.farmacia-exemplo.com.br/v1',
            'capabilities' => ['send_prescription', 'check_stock'],
            'connected_by' => $pharmacyDoctor?->user_id,
            'connected_at' => now()->subDays(5),
        ]);
        $farmacia->credential()->create([
            'auth_type' => 'bearer_token',
            'access_token' => hash('sha256', 'farmacia-bearer-token'),
            'token_expires_at' => now()->addYear(),
        ]);
        $this->attachDoctor($farmacia, $pharmacyDoctor, 'receive_only');
    }

    private function seedEvents(PartnerIntegration $partner, ?string $doctorId): void
    {
        IntegrationEvent::factory()->count(2)->outbound()->successful()->create([
            'partner_integration_id' => $partner->id,
            'doctor_id' => $doctorId,
        ]);
        IntegrationEvent::factory()->inbound()->successful()->create([
            'partner_integration_id' => $partner->id,
            'doctor_id' => $doctorId,
        ]);
        IntegrationEvent::factory()->outbound()->failed()->create([
            'partner_integration_id' => $partner->id,
            'doctor_id' => $doctorId,
        ]);
        IntegrationEvent::factory()->create([
            'partner_integration_id' => $partner->id,
            'doctor_id' => $doctorId,
            'status' => IntegrationEvent::STATUS_PENDING,
            'direction' => IntegrationEvent::DIRECTION_OUTBOUND,
        ]);
    }

    private function attachDoctor(PartnerIntegration $partner, ?Doctor $doctor, string $mode = 'full'): void
    {
        if (! $doctor) {
            return;
        }

        $partner->doctors()->syncWithoutDetaching([
            $doctor->id => [
                'integration_mode' => $mode,
                'perm_send_orders' => $mode === 'full',
                'perm_receive_results' => true,
                'perm_webhook' => true,
                'perm_patient_data' => false,
                'connected_by' => $doctor->user_id,
                'connected_at' => now()->subDays(2),
            ],
        ]);
    }

    private function seedWebhook(PartnerIntegration $partner): void
    {
        IntegrationWebhook::create([
            'partner_integration_id' => $partner->id,
            'url' => 'http://localhost/api/v1/public/webhooks/lab/'.$partner->slug,
            'events' => ['exam_result_received'],
            'status' => 'active',
        ]);
    }

    private function seedFhirMappings(PartnerIntegration $partner): void
    {
        $fakeUuid1 = \Illuminate\Support\Str::uuid()->toString();
        $fakeUuid2 = \Illuminate\Support\Str::uuid()->toString();

        FhirResourceMapping::create([
            'partner_integration_id' => $partner->id,
            'internal_resource_type' => 'patient',
            'internal_resource_id' => $fakeUuid1,
            'fhir_resource_type' => 'Patient',
            'fhir_resource_id' => 'fhir-patient-'.$fakeUuid1,
        ]);
        FhirResourceMapping::create([
            'partner_integration_id' => $partner->id,
            'internal_resource_type' => 'examination',
            'internal_resource_id' => $fakeUuid2,
            'fhir_resource_type' => 'DiagnosticReport',
            'fhir_resource_id' => 'fhir-report-'.$fakeUuid2,
        ]);
    }
}
