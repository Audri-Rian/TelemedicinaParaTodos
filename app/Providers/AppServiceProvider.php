<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Appointments;
use App\Observers\AppointmentsObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
