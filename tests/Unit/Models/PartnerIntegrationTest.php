<?php

namespace Tests\Unit\Models;

use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_active_filters_correctly(): void
    {
        PartnerIntegration::factory()->active()->create();
        PartnerIntegration::factory()->inactive()->create();
        PartnerIntegration::factory()->pending()->create();

        $this->assertCount(1, PartnerIntegration::active()->get());
    }

    public function test_scope_laboratories_filters_by_type(): void
    {
        PartnerIntegration::factory()->laboratory()->create();
        PartnerIntegration::factory()->pharmacy()->create();

        $this->assertCount(1, PartnerIntegration::laboratories()->get());
    }

    public function test_is_active_returns_correct_value(): void
    {
        $active = PartnerIntegration::factory()->active()->create();
        $inactive = PartnerIntegration::factory()->inactive()->create();

        $this->assertTrue($active->isActive());
        $this->assertFalse($inactive->isActive());
    }

    public function test_has_capability_checks_array(): void
    {
        $partner = PartnerIntegration::factory()->create([
            'capabilities' => ['send_exam_order', 'receive_exam_result'],
        ]);

        $this->assertTrue($partner->hasCapability('send_exam_order'));
        $this->assertFalse($partner->hasCapability('send_prescription'));
    }

    public function test_events_relationship_works(): void
    {
        $partner = PartnerIntegration::factory()->create();
        IntegrationEvent::factory()->count(3)->create([
            'partner_integration_id' => $partner->id,
        ]);

        $this->assertCount(3, $partner->events);
    }

    public function test_credential_relationship_works(): void
    {
        $partner = PartnerIntegration::factory()->withCredential()->create();

        $this->assertNotNull($partner->credential);
        $this->assertNotNull($partner->credential->client_id);
    }
}
