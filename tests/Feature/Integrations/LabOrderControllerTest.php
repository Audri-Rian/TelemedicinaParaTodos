<?php

namespace Tests\Feature\Integrations;

use App\Models\Examination;
use App\Models\IntegrationCredential;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LabOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIntegration $partner;
    private string $rawToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'slug' => 'test-lab-orders',
        ]);

        $this->rawToken = Str::random(64);

        $this->partner->credential()->create([
            'auth_type' => IntegrationCredential::AUTH_OAUTH2_CLIENT_CREDENTIALS,
            'client_id' => 'lab-orders-client',
            'client_secret' => bcrypt('secret'),
            'access_token' => hash('sha256', $this->rawToken),
            'token_expires_at' => now()->addHour(),
            'scopes' => ['lab:read', 'lab:write'],
        ]);
    }

    /**
     * Cria exame sem disparar o ExaminationObserver.
     */
    private function createExamWithoutEvents(array $attributes = []): Examination
    {
        return Examination::withoutEvents(fn () => Examination::factory()->create($attributes));
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

    public function test_authenticated_partner_gets_pending_orders(): void
    {
        // Criar exames pendentes para este parceiro
        $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'name' => 'Hemograma Completo',
            'status' => Examination::STATUS_IN_PROGRESS,
            'partner_integration_id' => $this->partner->id,
            'external_id' => 'EXT-ORDER-1',
        ]);

        $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'name' => 'Glicemia',
            'status' => Examination::STATUS_REQUESTED,
            'partner_integration_id' => $this->partner->id,
            'external_id' => 'EXT-ORDER-2',
        ]);

        // Exame completo — NÃO deve aparecer
        $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_COMPLETED,
            'partner_integration_id' => $this->partner->id,
            'external_id' => 'EXT-ORDER-3',
        ]);

        $response = $this->getJson(
            "/api/v1/public/lab/test-lab-orders/orders",
            ['Authorization' => "Bearer {$this->rawToken}"]
        );

        $response->assertOk()
            ->assertJsonPath('resourceType', 'Bundle')
            ->assertJsonPath('type', 'searchset')
            ->assertJsonPath('total', 2);

        $entries = $response->json('entry');
        $externalIds = collect($entries)->pluck('resource.id')->all();

        $this->assertContains('EXT-ORDER-1', $externalIds);
        $this->assertContains('EXT-ORDER-2', $externalIds);
        $this->assertNotContains('EXT-ORDER-3', $externalIds);
    }

    public function test_returns_empty_bundle_when_no_pending_orders(): void
    {
        $response = $this->getJson(
            "/api/v1/public/lab/test-lab-orders/orders",
            ['Authorization' => "Bearer {$this->rawToken}"]
        );

        $response->assertOk()
            ->assertJsonPath('resourceType', 'Bundle')
            ->assertJsonPath('total', 0)
            ->assertJsonPath('entry', []);
    }

    public function test_nonexistent_lab_slug_returns_404(): void
    {
        $response = $this->getJson(
            "/api/v1/public/lab/slug-inexistente/orders",
            ['Authorization' => "Bearer {$this->rawToken}"]
        );

        $this->assertContains($response->status(), [401, 404]);
    }

    public function test_fhir_bundle_structure_is_correct(): void
    {
        $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'name' => 'TSH',
            'status' => Examination::STATUS_REQUESTED,
            'partner_integration_id' => $this->partner->id,
            'external_id' => 'EXT-FHIR-1',
        ]);

        $response = $this->getJson(
            "/api/v1/public/lab/test-lab-orders/orders",
            ['Authorization' => "Bearer {$this->rawToken}"]
        );

        $response->assertOk()
            ->assertJsonStructure([
                'resourceType',
                'type',
                'total',
                'entry' => [
                    '*' => [
                        'resource' => [
                            'resourceType',
                            'id',
                            'status',
                            'intent',
                            'subject' => ['reference', 'display'],
                            'requester' => ['reference', 'display'],
                            'code' => ['text'],
                            'authoredOn',
                        ],
                    ],
                ],
            ]);

        $this->assertEquals('ServiceRequest', $response->json('entry.0.resource.resourceType'));
    }
}
