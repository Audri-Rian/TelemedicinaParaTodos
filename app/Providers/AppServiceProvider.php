<?php

namespace App\Providers;

use App\MedicalRecord\Domain\Contracts\ICPBrasilAdapter;
use App\MedicalRecord\Infrastructure\Persistence\Models\Examination;
use App\MedicalRecord\Infrastructure\Persistence\Models\MedicalCertificate;
use App\MedicalRecord\Infrastructure\Persistence\Models\Prescription;
use App\Models\Appointments;
use App\Observers\AppointmentsObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ICPBrasilAdapter::class, function () {
            $adapter = config('icp_brasil.providers.'.config('icp_brasil.adapter', 'unconfigured'));

            return $this->app->make($adapter);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Appointments::observe(AppointmentsObserver::class);
        Prescription::observe(\App\Observers\PrescriptionObserver::class);
        Examination::observe(\App\Observers\ExaminationObserver::class);
        MedicalCertificate::observe(\App\Observers\MedicalCertificateObserver::class);

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
