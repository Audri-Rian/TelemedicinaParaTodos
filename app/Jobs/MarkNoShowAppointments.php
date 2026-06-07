<?php

namespace App\Jobs;

use App\Models\Appointments;
use App\Services\AppointmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MarkNoShowAppointments implements ShouldQueue
{
    use Queueable;

    public function handle(AppointmentService $appointmentService): void
    {
        $graceMinutes = (int) config('telemedicine.maintenance.no_show_grace_minutes', 15);
        $cutoff = now()->subMinutes($graceMinutes);
        $marked = 0;

        Appointments::query()
            ->whereIn('status', [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])
            ->where('scheduled_at', '<=', $cutoff)
            ->orderBy('scheduled_at')
            ->chunkById(100, function ($appointments) use ($appointmentService, &$marked) {
                foreach ($appointments as $appointment) {
                    if ($appointmentService->markAsNoShow($appointment)) {
                        $marked++;
                    }
                }
            });

        if ($marked > 0) {
            Log::info('NO_SHOW_APPOINTMENTS_MARKED', [
                'count' => $marked,
                'grace_minutes' => $graceMinutes,
            ]);
        }
    }
}
