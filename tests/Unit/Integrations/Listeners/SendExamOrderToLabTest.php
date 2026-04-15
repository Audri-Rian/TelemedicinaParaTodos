<?php

namespace Tests\Unit\Integrations\Listeners;

use App\Events\ExaminationRequested;
use App\Integrations\Listeners\SendExamOrderToLab;
use App\Integrations\Services\IntegrationService;
use App\Models\Examination;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendExamOrderToLabTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_lab_exam_when_active_partner_exists(): void
    {
        PartnerIntegration::factory()->laboratory()->active()->create([
            'capabilities' => ['send_exam_order'],
        ]);

        $examination = Examination::factory()->create([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ]);

        $service = Mockery::mock(IntegrationService::class);
        $service->shouldReceive('sendExamOrder')
            ->with(Mockery::on(fn ($e) => $e->id === $examination->id))
            ->once();

        $this->app->instance(IntegrationService::class, $service);

        $listener = new SendExamOrderToLab();
        $listener->handle(new ExaminationRequested($examination));
    }

    public function test_ignores_non_lab_examination(): void
    {
        PartnerIntegration::factory()->laboratory()->active()->create();

        $examination = Examination::factory()->create([
            'type' => Examination::TYPE_IMAGE,
            'status' => Examination::STATUS_REQUESTED,
        ]);

        $service = Mockery::mock(IntegrationService::class);
        $service->shouldNotReceive('sendExamOrder');
        $this->app->instance(IntegrationService::class, $service);

        $listener = new SendExamOrderToLab();
        $listener->handle(new ExaminationRequested($examination));
    }

    public function test_ignores_when_no_active_lab_partner(): void
    {
        // Parceiro inativo não conta
        PartnerIntegration::factory()->laboratory()->inactive()->create();

        $examination = Examination::factory()->create([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ]);

        $service = Mockery::mock(IntegrationService::class);
        $service->shouldNotReceive('sendExamOrder');
        $this->app->instance(IntegrationService::class, $service);

        $listener = new SendExamOrderToLab();
        $listener->handle(new ExaminationRequested($examination));
    }

    public function test_catches_exception_without_breaking(): void
    {
        PartnerIntegration::factory()->laboratory()->active()->create();

        $examination = Examination::factory()->create([
            'type' => Examination::TYPE_LAB,
            'status' => Examination::STATUS_REQUESTED,
        ]);

        $service = Mockery::mock(IntegrationService::class);
        $service->shouldReceive('sendExamOrder')
            ->once()
            ->andThrow(new \RuntimeException('Erro inesperado'));

        $this->app->instance(IntegrationService::class, $service);

        // Não deve lançar exceção — o listener captura e reporta
        $listener = new SendExamOrderToLab();
        $listener->handle(new ExaminationRequested($examination));

        $this->assertTrue(true); // Se chegou aqui, não quebrou
    }
}
