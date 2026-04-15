<?php

namespace Tests\Unit\Models;

use App\Models\IntegrationQueueItem;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationQueueItemTest extends TestCase
{
    use RefreshDatabase;

    private PartnerIntegration $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->partner = PartnerIntegration::factory()->laboratory()->create();
    }

    // ─── hasReachedMaxAttempts (shouldRetry) ─────────────────────

    public function test_has_reached_max_attempts_is_false_below_threshold(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'attempts' => 2,
            'max_attempts' => 5,
        ]);

        $this->assertFalse($item->hasReachedMaxAttempts());
    }

    public function test_has_reached_max_attempts_is_true_at_threshold(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'attempts' => 5,
            'max_attempts' => 5,
        ]);

        $this->assertTrue($item->hasReachedMaxAttempts());
    }

    public function test_has_reached_max_attempts_is_true_above_threshold(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'attempts' => 6,
            'max_attempts' => 5,
        ]);

        $this->assertTrue($item->hasReachedMaxAttempts());
    }

    // ─── markProcessing ──────────────────────────────────────────

    public function test_mark_processing_updates_status_and_started_at(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationQueueItem::STATUS_QUEUED,
            'started_at' => null,
        ]);

        $item->markProcessing();

        $fresh = $item->fresh();
        $this->assertEquals(IntegrationQueueItem::STATUS_PROCESSING, $fresh->status);
        $this->assertNotNull($fresh->started_at);
    }

    // ─── markCompleted ───────────────────────────────────────────

    public function test_mark_completed_updates_status_and_completed_at(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationQueueItem::STATUS_PROCESSING,
        ]);

        $item->markCompleted();

        $fresh = $item->fresh();
        $this->assertEquals(IntegrationQueueItem::STATUS_COMPLETED, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    // ─── markFailed ──────────────────────────────────────────────

    public function test_mark_failed_requeues_when_under_max_attempts(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationQueueItem::STATUS_PROCESSING,
            'attempts' => 1,
            'max_attempts' => 5,
        ]);

        $item->markFailed('Temporary failure');

        $fresh = $item->fresh();
        $this->assertEquals(IntegrationQueueItem::STATUS_QUEUED, $fresh->status);
        $this->assertEquals(2, $fresh->attempts);
        $this->assertEquals('Temporary failure', $fresh->last_error);
    }

    public function test_mark_failed_sets_failed_when_max_attempts_reached(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationQueueItem::STATUS_PROCESSING,
            'attempts' => 5,
            'max_attempts' => 5,
        ]);

        $item->markFailed('Permanent failure');

        $fresh = $item->fresh();
        $this->assertEquals(IntegrationQueueItem::STATUS_FAILED, $fresh->status);
        $this->assertEquals(6, $fresh->attempts);
        $this->assertEquals('Permanent failure', $fresh->last_error);
    }

    public function test_mark_failed_increments_attempts(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'attempts' => 0,
            'max_attempts' => 3,
        ]);

        $item->markFailed('err');
        $this->assertEquals(1, $item->fresh()->attempts);
    }

    public function test_mark_failed_records_last_error(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'attempts' => 0,
            'max_attempts' => 3,
            'last_error' => null,
        ]);

        $item->markFailed('Connection refused');
        $this->assertEquals('Connection refused', $item->fresh()->last_error);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function test_scope_pending_returns_only_ready_queued_items(): void
    {
        // Ready: queued + scheduled_at null ou no passado
        IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationQueueItem::STATUS_QUEUED,
            'scheduled_at' => null,
        ]);
        IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationQueueItem::STATUS_QUEUED,
            'scheduled_at' => now()->subMinutes(10),
        ]);
        // Not ready: scheduled no futuro
        IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationQueueItem::STATUS_QUEUED,
            'scheduled_at' => now()->addHour(),
        ]);
        // Not ready: status != queued
        IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'status' => IntegrationQueueItem::STATUS_COMPLETED,
        ]);

        $pending = IntegrationQueueItem::query()->pending()->get();

        $this->assertCount(2, $pending);
    }

    public function test_scope_by_partner_filters_by_partner_id(): void
    {
        $other = PartnerIntegration::factory()->laboratory()->create();

        IntegrationQueueItem::factory()->count(3)->create([
            'partner_integration_id' => $this->partner->id,
        ]);
        IntegrationQueueItem::factory()->count(2)->create([
            'partner_integration_id' => $other->id,
        ]);

        $this->assertCount(3, IntegrationQueueItem::query()->byPartner($this->partner->id)->get());
        $this->assertCount(2, IntegrationQueueItem::query()->byPartner($other->id)->get());
    }

    // ─── Relacionamentos ────────────────────────────────────────

    public function test_belongs_to_partner_integration(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
        ]);

        $this->assertInstanceOf(PartnerIntegration::class, $item->partnerIntegration);
        $this->assertEquals($this->partner->id, $item->partnerIntegration->id);
    }

    // ─── Casts ──────────────────────────────────────────────────

    public function test_payload_is_cast_to_array(): void
    {
        $item = IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $this->partner->id,
            'payload' => ['foo' => 'bar', 'count' => 3],
        ]);

        $fresh = $item->fresh();
        $this->assertIsArray($fresh->payload);
        $this->assertEquals('bar', $fresh->payload['foo']);
        $this->assertEquals(3, $fresh->payload['count']);
    }
}
