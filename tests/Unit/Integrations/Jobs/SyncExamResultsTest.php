<?php

namespace Tests\Unit\Integrations\Jobs;

use App\Integrations\Services\IntegrationService;
use App\Integrations\Jobs\SyncExamResults;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncExamResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_all_active_laboratory_partners(): void
    {
        $partner1 = PartnerIntegration::factory()->laboratory()->active()->create();
        $partner2 = PartnerIntegration::factory()->laboratory()->active()->create();
        PartnerIntegration::factory()->laboratory()->inactive()->create(); // ignorado
        PartnerIntegration::factory()->pharmacy()->active()->create();    // ignorado

        $service = Mockery::mock(IntegrationService::class);
        $service->shouldReceive('syncExamResults')
            ->with(Mockery::on(fn ($p) => $p->id === $partner1->id))
            ->once()
            ->andReturn(3);
        $service->shouldReceive('syncExamResults')
            ->with(Mockery::on(fn ($p) => $p->id === $partner2->id))
            ->once()
            ->andReturn(0);

        $job = new SyncExamResults();
        $job->handle($service);

        // Se chegou aqui sem exceção, o job rodou normalmente
        $this->assertTrue(true);
    }

    public function test_throws_exception_when_partner_sync_fails(): void
    {
        PartnerIntegration::factory()->laboratory()->active()->create();

        $service = Mockery::mock(IntegrationService::class);
        $service->shouldReceive('syncExamResults')
            ->once()
            ->andThrow(new \RuntimeException('Falha na conexão'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('1 parceiro(s)');

        $job = new SyncExamResults();
        $job->handle($service);
    }

    public function test_does_nothing_when_no_active_labs(): void
    {
        // Sem parceiros — não deve chamar o service
        $service = Mockery::mock(IntegrationService::class);
        $service->shouldNotReceive('syncExamResults');

        $job = new SyncExamResults();
        $job->handle($service);

        $this->assertTrue(true);
    }
}
