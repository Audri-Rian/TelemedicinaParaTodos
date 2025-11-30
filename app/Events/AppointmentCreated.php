<?php

namespace App\Events;

use App\Models\Appointments;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Appointments $appointment)
    {
        $this->appointment->loadMissing(['doctor.user', 'patient.user']);
    }
}
