<?php

namespace App\Events;

use App\Models\Appointments;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Appointments $appointment,
        public ?string $reason = null
    ) {
        $this->appointment->loadMissing(['doctor.user', 'patient.user']);
    }
}
