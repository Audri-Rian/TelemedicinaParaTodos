<?php

namespace Database\Seeders;

use App\Models\IntegrationEvent;
use App\Models\FhirResourceMapping;
use App\Models\IntegrationWebhook;
use App\Models\PartnerIntegration;
use Illuminate\Database\Seeder;

class PartnerIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        // Lab Hermes (ativo, API key)
        $hermes = PartnerIntegration::factory()->laboratory()->active()->create([
            'name' => 'Lab Hermes Pardini',
            'slug' => 'hermes-pardini',
            'base_url' => 'https://api.hermespardini.com.br/fhir/r4',
            'contact_email' => 'integracao@hermespardini.com.br',
        ]);
        $hermes->credential()->create([
            'auth_type' => 'api_key',
            'client_id' => 'hermes-test-key-001',
        ]);
        $this->seedEvents($hermes);
        $this->seedWebhook($hermes);
        $this->seedFhirMappings($hermes);

        // Lab Fleury (ativo, OAuth2) — use para testar OAuth2 token endpoint
        $fleury = PartnerIntegration::factory()->laboratory()->active()->create([
            'name' => 'Fleury',
            'slug' => 'fleury',
            'base_url' => 'https://api.fleury.com.br/fhir/r4',
            'contact_email' => 'tech@fleury.com.br',
        ]);
        $fleury->credential()->create([
            'auth_type' => 'oauth2_client_credentials',
            'client_id' => 'fleury-oauth-client',
            'client_secret' => 'fleury-oauth-secret', // texto plano — encrypted cast protege at rest
        ]);

        // Farmácia Exemplo (inativa, Bearer)
        $farmacia = PartnerIntegration::factory()->pharmacy()->inactive()->create([
            'name' => 'Farmácia Exemplo',
            'slug' => 'farmacia-exemplo',
            'base_url' => 'https://api.farmacia-exemplo.com.br/v1',
            'capabilities' => ['send_prescription', 'check_stock'],
        ]);
        $farmacia->credential()->create([
            'auth_type' => 'bearer_token',
            'access_token' => hash('sha256', 'farmacia-bearer-token'),
            'token_expires_at' => now()->addYear(),
        ]);
    }

    private function seedEvents(PartnerIntegration $partner): void
    {
        IntegrationEvent::factory()->count(2)->outbound()->successful()->create([
            'partner_integration_id' => $partner->id,
        ]);
        IntegrationEvent::factory()->inbound()->successful()->create([
            'partner_integration_id' => $partner->id,
        ]);
        IntegrationEvent::factory()->outbound()->failed()->create([
            'partner_integration_id' => $partner->id,
        ]);
        IntegrationEvent::factory()->create([
            'partner_integration_id' => $partner->id,
            'status' => IntegrationEvent::STATUS_PENDING,
            'direction' => IntegrationEvent::DIRECTION_OUTBOUND,
        ]);
    }

    private function seedWebhook(PartnerIntegration $partner): void
    {
        IntegrationWebhook::create([
            'partner_integration_id' => $partner->id,
            'url' => 'http://localhost/api/v1/public/webhooks/lab/' . $partner->slug,
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
            'fhir_resource_id' => 'fhir-patient-' . $fakeUuid1,
        ]);
        FhirResourceMapping::create([
            'partner_integration_id' => $partner->id,
            'internal_resource_type' => 'examination',
            'internal_resource_id' => $fakeUuid2,
            'fhir_resource_type' => 'DiagnosticReport',
            'fhir_resource_id' => 'fhir-report-' . $fakeUuid2,
        ]);
    }
}
