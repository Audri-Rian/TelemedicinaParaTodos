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
        $leadMinutes = (int) config('telemedicine.video_call.window_lead_minutes', 10);
        $trailingMinutes = (int) config('telemedicine.video_call.window_trailing_minutes', 10);
        $now = Carbon::now();

        // Janela: [scheduled_at - lead, scheduled_at + trailing]
        $windowStart = $now->copy()->subMinutes($trailingMinutes);
        $windowEnd = $now->copy()->addMinutes($leadMinutes);

        $provisionedAppointmentIds = Call::where('call_type', Call::TYPE_SCHEDULED)
            ->whereNull('ended_at')
            ->whereIn('status', [Call::STATUS_ACCEPTED])
            ->whereNotNull('appointment_id')
            ->pluck('appointment_id');

        $appointments = Appointments::with(['patient.user', 'doctor'])
            ->whereIn('status', [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->whereNotIn('id', $provisionedAppointmentIds)
            ->get();

        foreach ($appointments as $appointment) {
            try {
                $callManager->provisionAppointmentCall($appointment);

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
