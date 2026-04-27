<?php

namespace App\Providers;

use App\Contracts\DigitalSignatureDriver;
use App\Contracts\MediaGatewayInterface;
use App\Models\Appointments;
use App\Observers\AppointmentsObserver;
use App\Services\MediaGatewayHttp;
use App\Services\MediaGatewayStub;
use App\Services\Signatures\DigitalSignatureService;
use App\Services\Signatures\IcpBrasilSignatureDriver;
use App\Services\Signatures\NullSignatureDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $httpUrl = (string) config('services.media_gateway.sfu_http_url');
        $apiSecret = (string) config('services.media_gateway.api_secret');

        if ($httpUrl !== '' && $apiSecret !== '') {
            $this->app->bind(MediaGatewayInterface::class, MediaGatewayHttp::class);
        } else {
            $this->app->bind(MediaGatewayInterface::class, MediaGatewayStub::class);
        }

        $this->app->bind(DigitalSignatureDriver::class, function () {
            return match (config('telemedicine.signature.driver', 'null')) {
                'icp_brasil' => new IcpBrasilSignatureDriver,
                default => new NullSignatureDriver,
            };
        });

        $this->app->singleton(DigitalSignatureService::class, function ($app) {
            return new DigitalSignatureService($app->make(DigitalSignatureDriver::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Appointments::observe(AppointmentsObserver::class);
        \App\Models\Prescription::observe(\App\Observers\PrescriptionObserver::class);
        \App\Models\Examination::observe(\App\Observers\ExaminationObserver::class);
        \App\Models\MedicalCertificate::observe(\App\Observers\MedicalCertificateObserver::class);

        // Registrar listeners de notificações
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\AppointmentCreated::class,
            [\App\Listeners\SendNotificationListener::class, 'handleAppointmentCreated']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\AppointmentCancelled::class,
            [\App\Listeners\SendNotificationListener::class, 'handleAppointmentCancelled']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\AppointmentRescheduled::class,
            [\App\Listeners\SendNotificationListener::class, 'handleAppointmentRescheduled']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PrescriptionIssued::class,
            [\App\Listeners\SendNotificationListener::class, 'handlePrescriptionIssued']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ExaminationRequested::class,
            [\App\Listeners\SendNotificationListener::class, 'handleExaminationRequested']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MedicalCertificateIssued::class,
            [\App\Listeners\SendNotificationListener::class, 'handleMedicalCertificateIssued']
        );
    }
}
