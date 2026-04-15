<?php

namespace Tests\Unit\Integrations\Services;

use App\Integrations\Contracts\LabIntegrationInterface;
use App\Integrations\DTOs\ExamResultDto;
use App\Integrations\Events\ExamOrderSent;
use App\Integrations\Events\ExamResultReceived;
use App\Integrations\Events\IntegrationFailed;
use App\Integrations\Services\CircuitBreaker;
use App\Integrations\Services\IntegrationService;
use App\Models\Examination;
use App\Models\FhirResourceMapping;
use App\Models\IntegrationEvent;
use App\Models\IntegrationQueueItem;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class IntegrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private IntegrationService $service;
    private LabIntegrationInterface $labAdapter;
    private CircuitBreaker $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->labAdapter = Mockery::mock(LabIntegrationInterface::class);
        $this->circuitBreaker = Mockery::mock(CircuitBreaker::class);

        $this->service = new IntegrationService($this->labAdapter, $this->circuitBreaker);
    }

    /**
     * Cria exame sem disparar o ExaminationObserver (que acionaria o fluxo real).
     */
    private function createExamWithoutEvents(array $attributes = []): Examination
    {
        return Examination::withoutEvents(fn () => Examination::factory()->create($attributes));
    }

    // ─── sendExamOrder ───────────────────────────────────────────

    public function test_send_exam_order_happy_path(): void
    {
        Event::fake([ExamOrderSent::class]);

        $partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'capabilities' => ['send_exam_order', 'receive_exam_result'],
        ]);

        $examination = $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ]);

        $this->circuitBreaker->shouldReceive('isAvailable')->with($partner->id)->andReturn(true);
        $this->circuitBreaker->shouldReceive('recordSuccess')->with($partner->id)->once();

        $this->labAdapter->shouldReceive('sendOrder')
            ->once()
            ->andReturn(['external_id' => 'EXT-123', 'status' => 'sent']);

        $this->service->sendExamOrder($examination);

        $examination->refresh();
        $this->assertEquals('EXT-123', $examination->external_id);
        $this->assertEquals(Examination::STATUS_IN_PROGRESS, $examination->status);
        $this->assertEquals($partner->id, $examination->partner_integration_id);

        $this->assertDatabaseHas('fhir_resource_mappings', [
            'internal_resource_type' => 'examination',
            'internal_resource_id' => $examination->id,
            'fhir_resource_id' => 'EXT-123',
            'partner_integration_id' => $partner->id,
        ]);

        $this->assertDatabaseHas('integration_events', [
            'partner_integration_id' => $partner->id,
            'direction' => IntegrationEvent::DIRECTION_OUTBOUND,
            'event_type' => IntegrationEvent::EVENT_EXAM_ORDER_SENT,
            'status' => IntegrationEvent::STATUS_SUCCESS,
            'external_id' => 'EXT-123',
        ]);

        Event::assertDispatched(ExamOrderSent::class, function ($event) use ($examination) {
            return $event->examination->id === $examination->id
                && $event->externalId === 'EXT-123';
        });
    }

    public function test_send_exam_order_without_active_partner(): void
    {
        Event::fake([ExamOrderSent::class]);

        $examination = $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ]);

        $this->service->sendExamOrder($examination);

        Event::assertNotDispatched(ExamOrderSent::class);
        $this->assertDatabaseCount('integration_events', 0);
        $this->assertEquals(Examination::STATUS_REQUESTED, $examination->fresh()->status);
    }

    public function test_send_exam_order_skips_if_already_synced(): void
    {
        $partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'capabilities' => ['send_exam_order'],
        ]);

        $examination = $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ]);

        FhirResourceMapping::create([
            'internal_resource_type' => 'examination',
            'internal_resource_id' => $examination->id,
            'fhir_resource_type' => FhirResourceMapping::FHIR_SERVICE_REQUEST,
            'fhir_resource_id' => 'EXT-OLD',
            'partner_integration_id' => $partner->id,
            'synced_at' => now(),
        ]);

        $this->labAdapter->shouldNotReceive('sendOrder');

        $this->service->sendExamOrder($examination);

        $this->assertDatabaseCount('integration_events', 0);
    }

    public function test_send_exam_order_enqueues_when_circuit_breaker_open(): void
    {
        $partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'capabilities' => ['send_exam_order'],
        ]);

        $examination = $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ]);

        $this->circuitBreaker->shouldReceive('isAvailable')->with($partner->id)->andReturn(false);

        $this->labAdapter->shouldNotReceive('sendOrder');

        $this->service->sendExamOrder($examination);

        $this->assertDatabaseHas('integration_queue', [
            'partner_integration_id' => $partner->id,
            'operation' => IntegrationQueueItem::OP_SEND_EXAM_ORDER,
            'status' => IntegrationQueueItem::STATUS_QUEUED,
        ]);
    }

    public function test_send_exam_order_handles_adapter_failure(): void
    {
        Event::fake([IntegrationFailed::class, ExamOrderSent::class]);

        $partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'capabilities' => ['send_exam_order'],
        ]);

        $examination = $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ]);

        $this->circuitBreaker->shouldReceive('isAvailable')->with($partner->id)->andReturn(true);
        $this->circuitBreaker->shouldReceive('recordFailure')->with($partner->id)->once();

        $this->labAdapter->shouldReceive('sendOrder')
            ->once()
            ->andThrow(new \RuntimeException('Timeout ao conectar com o laboratório'));

        $this->service->sendExamOrder($examination);

        $this->assertNull($examination->fresh()->external_id);
        $this->assertEquals(Examination::STATUS_REQUESTED, $examination->fresh()->status);

        $this->assertDatabaseHas('integration_events', [
            'partner_integration_id' => $partner->id,
            'status' => IntegrationEvent::STATUS_FAILED,
            'error_message' => 'Timeout ao conectar com o laboratório',
        ]);

        $this->assertDatabaseHas('integration_queue', [
            'partner_integration_id' => $partner->id,
            'operation' => IntegrationQueueItem::OP_SEND_EXAM_ORDER,
        ]);

        Event::assertDispatched(IntegrationFailed::class);
        Event::assertNotDispatched(ExamOrderSent::class);
    }

    public function test_send_exam_order_uses_existing_partner_from_examination(): void
    {
        Event::fake([ExamOrderSent::class]);

        $partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'capabilities' => ['send_exam_order'],
        ]);

        $examination = $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
            'partner_integration_id' => $partner->id,
        ]);

        $this->circuitBreaker->shouldReceive('isAvailable')->andReturn(true);
        $this->circuitBreaker->shouldReceive('recordSuccess');
        $this->labAdapter->shouldReceive('sendOrder')->once()
            ->andReturn(['external_id' => 'EXT-456', 'status' => 'sent']);

        $this->service->sendExamOrder($examination);

        $this->assertEquals('EXT-456', $examination->fresh()->external_id);
    }

    // ─── syncExamResults ─────────────────────────────────────────

    public function test_sync_exam_results_happy_path(): void
    {
        Event::fake([ExamResultReceived::class]);

        $partner = PartnerIntegration::factory()->laboratory()->active()->create();

        $examination = $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_IN_PROGRESS,
            'partner_integration_id' => $partner->id,
            'external_id' => 'EXT-789',
        ]);

        $resultDto = new ExamResultDto(
            externalId: 'EXT-789',
            examinationId: $examination->id,
            status: 'final',
            results: [['name' => 'Hemoglobina', 'value' => 14.5, 'unit' => 'g/dL']],
            completedAt: now()->toIso8601String(),
        );

        $this->circuitBreaker->shouldReceive('isAvailable')->with($partner->id)->andReturn(true);
        $this->circuitBreaker->shouldReceive('recordSuccess')->with($partner->id)->once();

        $this->labAdapter->shouldReceive('fetchResult')
            ->with($partner, 'EXT-789')
            ->once()
            ->andReturn($resultDto);

        $received = $this->service->syncExamResults($partner);

        $this->assertEquals(1, $received);

        $examination->refresh();
        $this->assertEquals(Examination::STATUS_COMPLETED, $examination->status);
        $this->assertEquals(Examination::SOURCE_INTEGRATION, $examination->source);
        $this->assertNotNull($examination->completed_at);
        $this->assertNotNull($examination->received_from_partner_at);

        $this->assertDatabaseHas('integration_events', [
            'partner_integration_id' => $partner->id,
            'direction' => IntegrationEvent::DIRECTION_INBOUND,
            'event_type' => IntegrationEvent::EVENT_EXAM_RESULT_RECEIVED,
            'status' => IntegrationEvent::STATUS_SUCCESS,
        ]);

        Event::assertDispatched(ExamResultReceived::class);
    }

    public function test_sync_exam_results_skips_when_circuit_breaker_open(): void
    {
        $partner = PartnerIntegration::factory()->laboratory()->active()->create();

        $this->circuitBreaker->shouldReceive('isAvailable')->with($partner->id)->andReturn(false);

        $received = $this->service->syncExamResults($partner);

        $this->assertEquals(0, $received);
    }

    public function test_sync_exam_results_skips_when_no_result_available(): void
    {
        $partner = PartnerIntegration::factory()->laboratory()->active()->create();

        $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_IN_PROGRESS,
            'partner_integration_id' => $partner->id,
            'external_id' => 'EXT-NO-RESULT',
        ]);

        $this->circuitBreaker->shouldReceive('isAvailable')->with($partner->id)->andReturn(true);

        $this->labAdapter->shouldReceive('fetchResult')->once()->andReturn(null);

        $received = $this->service->syncExamResults($partner);

        $this->assertEquals(0, $received);
    }

    // ─── processQueue ────────────────────────────────────────────

    public function test_process_queue_retries_pending_items(): void
    {
        Event::fake([ExamOrderSent::class]);

        $partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'capabilities' => ['send_exam_order'],
        ]);

        $examination = $this->createExamWithoutEvents([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ]);

        IntegrationQueueItem::factory()->pending()->create([
            'partner_integration_id' => $partner->id,
            'operation' => IntegrationQueueItem::OP_SEND_EXAM_ORDER,
            'payload' => ['examination_id' => $examination->id],
        ]);

        $this->circuitBreaker->shouldReceive('isAvailable')->andReturn(true);
        $this->circuitBreaker->shouldReceive('recordSuccess');

        $this->labAdapter->shouldReceive('sendOrder')->once()
            ->andReturn(['external_id' => 'EXT-RETRY', 'status' => 'sent']);

        $processed = $this->service->processQueue();

        $this->assertEquals(1, $processed);
        $this->assertDatabaseHas('integration_queue', [
            'partner_integration_id' => $partner->id,
            'status' => IntegrationQueueItem::STATUS_COMPLETED,
        ]);
    }

    public function test_process_queue_skips_items_with_circuit_breaker_open(): void
    {
        $partner = PartnerIntegration::factory()->laboratory()->active()->create();

        IntegrationQueueItem::factory()->pending()->create([
            'partner_integration_id' => $partner->id,
        ]);

        $this->circuitBreaker->shouldReceive('isAvailable')
            ->with($partner->id)
            ->andReturn(false);

        $processed = $this->service->processQueue();

        $this->assertEquals(0, $processed);
    }
}
