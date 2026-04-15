<?php

namespace Tests\Unit\Integrations\Jobs;

use App\Integrations\Jobs\ProcessIntegrationQueue;
use App\Integrations\Services\IntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProcessIntegrationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_calls_process_queue_on_service(): void
    {
        $service = Mockery::mock(IntegrationService::class);
        $service->shouldReceive('processQueue')
            ->once()
            ->andReturn(5);

        $job = new ProcessIntegrationQueue();
        $job->handle($service);

        $this->assertTrue(true);
    }

    public function test_handles_zero_items_gracefully(): void
    {
        $service = Mockery::mock(IntegrationService::class);
        $service->shouldReceive('processQueue')
            ->once()
            ->andReturn(0);

        $job = new ProcessIntegrationQueue();
        $job->handle($service);

        $this->assertTrue(true);
    }
}
