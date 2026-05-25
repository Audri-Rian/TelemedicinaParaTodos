<?php

namespace App\Jobs;

use App\Models\Appointments;
use App\Models\Call;
use App\Services\CallManagerService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AutoStartVideoCall implements ShouldQueue
{
    use Queueable;

    public function handle(CallManagerService $callManager): void
    {
        $leadMinutes = (int) config('telemedicine.appointment.lead_minutes', 10);
        $trailingMinutes = (int) config('telemedicine.appointment.trailing_minutes', 10);
        $now = Carbon::now();
        $windowStart = $now->copy()->subMinutes($trailingMinutes);
        $windowEnd = $now->copy()->addMinutes($leadMinutes);

        $activeAppointmentIds = Call::whereIn('status', [
            Call::STATUS_REQUESTED,
            Call::STATUS_RINGING,
            Call::STATUS_ACCEPTED,
        ])->pluck('appointment_id');

        $appointments = Appointments::with(['patient.user', 'doctor'])
            ->whereIn('status', [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->whereNotIn('id', $activeAppointmentIds)
            ->get();

        foreach ($appointments as $appointment) {
            try {
                $patientUser = $appointment->patient->user;
                $callManager->createCall($appointment, $patientUser);

                Log::info('AUTO_CALL_CREATED', [
                    'appointment_id' => $appointment->id,
                    'scheduled_at' => $appointment->scheduled_at,
                ]);
            } catch (\Throwable $e) {
                Log::warning('AUTO_CALL_FAILED', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
