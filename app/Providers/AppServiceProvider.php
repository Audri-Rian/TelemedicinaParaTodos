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
    }
}
