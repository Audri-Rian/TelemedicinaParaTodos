<?php

namespace App\Providers;

use App\Models\Appointments;
use App\Models\ServiceLocation;
use App\Models\AvailabilitySlot;
use App\Models\Doctor\BlockedDate;
use App\Policies\AppointmentPolicy;
use App\Policies\Doctor\ServiceLocationPolicy;
use App\Policies\Doctor\AvailabilitySlotPolicy;
use App\Policies\Doctor\BlockedDatePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Appointments::class => AppointmentPolicy::class,
        ServiceLocation::class => ServiceLocationPolicy::class,
        AvailabilitySlot::class => AvailabilitySlotPolicy::class,
        BlockedDate::class => BlockedDatePolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
