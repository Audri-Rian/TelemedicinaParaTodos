<?php

namespace App\Providers;

use App\Models\Appointments;
use App\Models\AvailabilitySlot;
use App\Models\Doctor;
use App\Models\Doctor\BlockedDate;
use App\Models\Message;
use App\Models\Patient;
use App\Models\ServiceLocation;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\Doctor\AvailabilitySlotPolicy;
use App\Policies\Doctor\BlockedDatePolicy;
use App\Policies\Doctor\ServiceLocationPolicy;
use App\Policies\MedicalRecordPolicy;
use App\Policies\MessagePolicy;
use App\Policies\TimelineEventPolicy;
use App\Policies\VideoCallPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Appointments::class => AppointmentPolicy::class,
        Message::class => MessagePolicy::class,
        ServiceLocation::class => ServiceLocationPolicy::class,
        AvailabilitySlot::class => AvailabilitySlotPolicy::class,
        BlockedDate::class => BlockedDatePolicy::class,
        TimelineEvent::class => TimelineEventPolicy::class,
        Patient::class => MedicalRecordPolicy::class,
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

        Gate::define('viewConversation', [ConversationPolicy::class, 'viewConversation']);
        Gate::define('sendMessage', [ConversationPolicy::class, 'sendMessage']);
        Gate::define('sendMessageInAppointment', [ConversationPolicy::class, 'sendMessageInAppointment']);

        Gate::define('manageDoctorSchedule', function (User $user, Doctor $doctor) {
            return $user->doctor && (string) $user->doctor->id === (string) $doctor->id;
        });

        Gate::define('video-call-request', [VideoCallPolicy::class, 'request']);
        Gate::define('video-call-request-adhoc', [VideoCallPolicy::class, 'requestAdhoc']);
        Gate::define('video-call-accept', [VideoCallPolicy::class, 'accept']);
        Gate::define('video-call-reject', [VideoCallPolicy::class, 'reject']);
        Gate::define('video-call-end', [VideoCallPolicy::class, 'end']);
        Gate::define('video-call-view', [VideoCallPolicy::class, 'view']);
        Gate::define('video-call-view-active', [VideoCallPolicy::class, 'viewActive']);
        Gate::define('video-call-join-session', [VideoCallPolicy::class, 'joinSession']);
    }
}
