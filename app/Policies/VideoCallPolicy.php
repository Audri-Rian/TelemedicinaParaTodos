<?php

namespace App\Policies;

use App\Models\Appointments;
use App\Models\User;

class VideoCallPolicy
{
    public function request(User $user, Appointments $appointment): bool
    {
        return $this->canParticipateInAppointment($user, $appointment)
            && in_array($appointment->status, [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_IN_PROGRESS,
            ], true);
    }

    public function accept(User $user, Appointments $appointment): bool
    {
        return $this->request($user, $appointment);
    }

    public function reject(User $user, Appointments $appointment): bool
    {
        return $this->request($user, $appointment);
    }

    public function end(User $user, Appointments $appointment): bool
    {
        return $this->request($user, $appointment);
    }

    protected function canParticipateInAppointment(User $user, Appointments $appointment): bool
    {
        if ($user->doctor && $appointment->doctor_id === $user->doctor->id) {
            return true;
        }

        if ($user->patient && $appointment->patient_id === $user->patient->id) {
            return true;
        }

        return false;
    }
}
