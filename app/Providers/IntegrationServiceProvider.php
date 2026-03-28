<?php

namespace App\Providers;

use App\Integrations\Adapters\Lab\FhirLabAdapter;
use App\Integrations\Adapters\Lab\LabAdapterStub;
use App\Integrations\Contracts\LabIntegrationInterface;
use App\Integrations\Events\ExamResultReceived;
use App\Integrations\Events\IntegrationFailed;
use App\Integrations\Listeners\NotifyIntegrationFailure;
use App\Integrations\Listeners\ProcessExamResult;
use App\Integrations\Listeners\SendExamOrderToLab;
use App\Integrations\Services\IntegrationService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class IntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(IntegrationService::class);

        // Lab adapter: usa stub em dev/test, FHIR real em staging/produção
        if (app()->environment('local', 'testing')) {
            $this->app->bind(LabIntegrationInterface::class, LabAdapterStub::class);
        } else {
            $this->app->bind(LabIntegrationInterface::class, FhirLabAdapter::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Listener: quando um exame é criado e há parceiro lab ativo, envia pedido
        Event::listen(
            \App\Events\ExaminationRequested::class,
            [SendExamOrderToLab::class, 'handle']
        );

        // Listener: quando resultado chega via integração, processa notificações
        Event::listen(
            ExamResultReceived::class,
            [ProcessExamResult::class, 'handle']
        );

        // Listener: quando integração falha, notifica admin
        Event::listen(
            IntegrationFailed::class,
            [NotifyIntegrationFailure::class, 'handle']
        );
    }
}
