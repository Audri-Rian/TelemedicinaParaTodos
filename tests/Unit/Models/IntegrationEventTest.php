<?php

namespace Tests\Unit\Models;

use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationEventTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIntegration $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->partner = PartnerIntegration::factory()->laboratory()->create();
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function test_scope_successful_filters_by_status(): void
    {
        IntegrationEvent::factory()->count(2)->successful()->create([
            'partner_integration_id' => $this->partner->id,
        ]);
        IntegrationEvent::factory()->failed()->create([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->assertCount(2, IntegrationEvent::query()->successful()->get());
    }

    public function test_scope_failed_filters_by_status(): void
    {
        IntegrationEvent::factory()->successful()->create([
            'partner_integration_id' => $this->partner->id,
        ]);
        IntegrationEvent::factory()->count(3)->failed()->create([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->assertCount(3, IntegrationEvent::query()->failed()->get());
    }

    public function test_scope_outbound_filters_by_direction(): void
    {
        IntegrationEvent::factory()->count(2)->outbound()->create([
            'partner_integration_id' => $this->partner->id,
        ]);
        IntegrationEvent::factory()->inbound()->create([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->assertCount(2, IntegrationEvent::query()->outbound()->get());
    }

    public function test_scope_inbound_filters_by_direction(): void
    {
        IntegrationEvent::factory()->outbound()->create([
            'partner_integration_id' => $this->partner->id,
        ]);
        IntegrationEvent::factory()->count(4)->inbound()->create([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->assertCount(4, IntegrationEvent::query()->inbound()->get());
    }

    public function test_scope_for_resource_filters_by_type_and_id(): void
    {
        $uuid1 = (string) \Illuminate\Support\Str::uuid();
        $uuid2 = (string) \Illuminate\Support\Str::uuid();

        IntegrationEvent::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'resource_type' => 'examination',
            'resource_id' => $uuid1,
        ]);
        IntegrationEvent::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'resource_type' => 'examination',
            'resource_id' => $uuid2,
        ]);
        IntegrationEvent::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'resource_type' => 'prescription',
            'resource_id' => $uuid1,
        ]);

        $found = IntegrationEvent::query()->forResource('examination', $uuid1)->get();
        $this->assertCount(1, $found);
        $this->assertEquals('examination', $found->first()->resource_type);
        $this->assertEquals($uuid1, $found->first()->resource_id);
    }

    // ─── Métodos ─────────────────────────────────────────────────

    public function test_is_success_returns_true_for_success_status(): void
    {
        $event = IntegrationEvent::factory()->successful()->create([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->assertTrue($event->isSuccess());
    }

    public function test_is_success_returns_false_for_non_success_status(): void
    {
        $event = IntegrationEvent::factory()->failed()->create([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->assertFalse($event->isSuccess());
    }

    public function test_is_failed_returns_true_for_failed_status(): void
    {
        $event = IntegrationEvent::factory()->failed()->create([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->assertTrue($event->isFailed());
    }

    public function test_is_failed_returns_false_for_success(): void
    {
        $event = IntegrationEvent::factory()->successful()->create([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->assertFalse($event->isFailed());
    }

    public function test_can_retry_is_true_for_failed_or_retrying(): void
    {
        $failed = IntegrationEvent::factory()->failed()->create([
            'partner_integration_id' => $this->partner->id,
        ]);
        $retrying = IntegrationEvent::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationEvent::STATUS_RETRYING,
        ]);

        $this->assertTrue($failed->canRetry());
        $this->assertTrue($retrying->canRetry());
    }

    public function test_can_retry_is_false_for_success_pending_and_processing(): void
    {
        $success = IntegrationEvent::factory()->successful()->create([
            'partner_integration_id' => $this->partner->id,
        ]);
        $pending = IntegrationEvent::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationEvent::STATUS_PENDING,
        ]);
        $processing = IntegrationEvent::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationEvent::STATUS_PROCESSING,
        ]);

        $this->assertFalse($success->canRetry());
        $this->assertFalse($pending->canRetry());
        $this->assertFalse($processing->canRetry());
    }

    // ─── Casts ──────────────────────────────────────────────────

    public function test_payloads_are_cast_to_array(): void
    {
        $event = IntegrationEvent::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'request_payload' => ['foo' => 'bar'],
            'response_payload' => ['baz' => 'qux'],
        ]);

        $fresh = $event->fresh();
        $this->assertIsArray($fresh->request_payload);
        $this->assertEquals('bar', $fresh->request_payload['foo']);
        $this->assertIsArray($fresh->response_payload);
        $this->assertEquals('qux', $fresh->response_payload['baz']);
    }

    // ─── Constantes ──────────────────────────────────────────────

    public function test_direction_and_status_constants_exist(): void
    {
        $this->assertEquals('outbound', IntegrationEvent::DIRECTION_OUTBOUND);
        $this->assertEquals('inbound', IntegrationEvent::DIRECTION_INBOUND);
        $this->assertEquals('success', IntegrationEvent::STATUS_SUCCESS);
        $this->assertEquals('failed', IntegrationEvent::STATUS_FAILED);
        $this->assertEquals('rnds_submitted', IntegrationEvent::EVENT_RNDS_SUBMITTED);
    }
}
