<?php

namespace App\Providers;

use App\Contracts\DigitalSignatureDriver;
use App\Contracts\MediaGatewayInterface;
use App\Contracts\Notifications\PushNotificationSender;
use App\Contracts\PdfSigner;
use App\Models\Appointments;
use App\Observers\AppointmentsObserver;
use App\Services\MediaGatewayHttp;
use App\Services\MediaGatewayStub;
use App\Services\Notifications\NullPushSender;
use App\Services\Notifications\WebPushSender;
use App\Services\Signatures\A1PdfSigner;
use App\Services\Signatures\DigitalSignatureService;
use App\Services\Signatures\IcpBrasilSignatureDriver;
use App\Services\Signatures\NullPdfSigner;
use App\Services\Signatures\NullSignatureDriver;
use App\Support\Signatures\PadesEmbedder;
use App\Support\StorageDomainConfigValidator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var list<string> */
    private const PDF_SIGNATURE_DRIVERS = ['null', 'a1_local'];

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

        $this->app->bind(PdfSigner::class, function () {
            return match ($this->pdfSignatureDriver()) {
                'a1_local' => new A1PdfSigner(new PadesEmbedder),
                default => new NullPdfSigner,
            };
        });

        $this->app->bind(PushNotificationSender::class, function () {
            return config('telemedicine.push.enabled')
                ? new WebPushSender
                : new NullPushSender;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $driver = $this->pdfSignatureDriver();

        // Guard: NullPdfSigner has no legal validity. CFM Res. 2.314/2022 Art. 8 requires ICP-Brasil.
        if ($this->app->isProduction() && $driver !== 'a1_local') {
            throw new \RuntimeException(
                "SIGNATURE_DRIVER={$driver} is not permitted in production. ".
                'Set SIGNATURE_DRIVER=a1_local in .env and configure SIGNATURE_A1_PFX_PATH.'
            );
        }

        StorageDomainConfigValidator::validate();

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

    private function pdfSignatureDriver(): string
    {
        $configured = config('telemedicine.signature.driver');
        $driver = ($configured === null || $configured === '') ? 'null' : (string) $configured;

        if (! in_array($driver, self::PDF_SIGNATURE_DRIVERS, true)) {
            throw new \RuntimeException(sprintf(
                'Invalid SIGNATURE_DRIVER "%s" for PDF signing. Allowed values: %s.',
                $driver,
                implode(', ', self::PDF_SIGNATURE_DRIVERS),
            ));
        }

        return $driver;
    }
}
