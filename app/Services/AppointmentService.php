<?php

namespace App\Services;

use App\Models\Appointments;
use Carbon\Carbon;

class AppointmentService
{
    public function isUpcoming(Appointments $appointment): bool
    {
        return $appointment->scheduled_at > Carbon::now() &&
            in_array($appointment->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED]);
    }

    public function isPast(Appointments $appointment): bool
    {
        return $appointment->scheduled_at < Carbon::now();
    }

    public function isActive(Appointments $appointment): bool
    {
        return $appointment->status === Appointments::STATUS_IN_PROGRESS;
    }

    public function canBeStarted(Appointments $appointment): bool
    {
        return $appointment->status === Appointments::STATUS_SCHEDULED &&
            $appointment->scheduled_at <= Carbon::now()->addMinutes(15);
    }

    public function canBeCancelled(Appointments $appointment): bool
    {
        return in_array($appointment->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED]) &&
            $appointment->scheduled_at > Carbon::now()->addHours(2);
    }

    public function start(Appointments $appointment): bool
    {
        if (!$this->canBeStarted($appointment)) {
            return false;
        }

        $appointment->update([
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now(),
        ]);

        return true;
    }

    public function end(Appointments $appointment): bool
    {
        if ($appointment->status !== Appointments::STATUS_IN_PROGRESS) {
            return false;
        }

        $appointment->update([
            'status' => Appointments::STATUS_COMPLETED,
            'ended_at' => Carbon::now(),
        ]);

        return true;
    }

    public function cancel(Appointments $appointment, ?string $reason = null): bool
    {
        if (!$this->canBeCancelled($appointment)) {
            return false;
        }

        $appointment->update([
            'status' => Appointments::STATUS_CANCELLED,
            'notes' => $reason ? ($appointment->notes . "\nCancelado: " . $reason) : $appointment->notes,
        ]);

        return true;
    }

    public function markAsNoShow(Appointments $appointment): bool
    {
        if ($appointment->status !== Appointments::STATUS_SCHEDULED) {
            return false;
        }

        $appointment->update([
            'status' => Appointments::STATUS_NO_SHOW,
        ]);

        return true;
    }

    public function reschedule(Appointments $appointment, Carbon $newDateTime): bool
    {
        if (!in_array($appointment->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])) {
            return false;
        }

        $appointment->update([
            'scheduled_at' => $newDateTime,
            'status' => Appointments::STATUS_RESCHEDULED,
        ]);

        return true;
    }
}


