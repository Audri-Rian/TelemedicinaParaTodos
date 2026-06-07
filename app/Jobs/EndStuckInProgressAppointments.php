<?php

namespace App\Jobs;

use App\Models\Appointments;
use App\Models\Call;
use App\Services\AppointmentService;
use App\Services\CallManagerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EndStuckInProgressAppointments implements ShouldQueue
{
    use Queueable;

    public function handle(
        AppointmentService $appointmentService,
        CallManagerService $callManager
    ): void {
        $maxMinutes = (int) config('telemedicine.video_call.in_progress_max_minutes', 120);
        $cutoff = now()->subMinutes($maxMinutes);
        $ended = 0;
        $skipped = 0;

        Appointments::query()
            ->where('status', Appointments::STATUS_IN_PROGRESS)
            ->where('scheduled_at', '<=', $cutoff)
            ->orderBy('scheduled_at')
            ->chunkById(100, function ($appointments) use ($appointmentService, $callManager, &$ended, &$skipped) {
                foreach ($appointments as $appointment) {
                    try {
                        if ($this->endStuckAppointment($appointment, $appointmentService, $callManager)) {
                            $ended++;
                        } else {
                            $skipped++;
                        }
                    } catch (\Throwable $exception) {
                        Log::error('STUCK_IN_PROGRESS_END_FAILED', [
                            'appointment_id' => $appointment->id,
                            'exception' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        if ($ended > 0 || $skipped > 0) {
            Log::info('STUCK_IN_PROGRESS_APPOINTMENTS_PROCESSED', [
                'ended_count' => $ended,
                'skipped_active_count' => $skipped,
                'max_minutes' => $maxMinutes,
            ]);
        }
    }

    private function endStuckAppointment(
        Appointments $appointment,
        AppointmentService $appointmentService,
        CallManagerService $callManager
    ): bool {
        $activeCall = Call::with('room')
            ->where('appointment_id', $appointment->id)
            ->whereIn('status', [Call::STATUS_REQUESTED, Call::STATUS_RINGING, Call::STATUS_ACCEPTED])
            ->whereNull('ended_at')
            ->first();

        // Sessão em curso nunca é derrubada (Q3) — só novos joins ficam bloqueados pela policy
        if ($activeCall && ($activeCall->doctor_joined_at || $activeCall->patient_joined_at)) {
            Log::info('STUCK_IN_PROGRESS_SKIPPED_ACTIVE_CALL', [
                'appointment_id' => $appointment->id,
                'call_id' => $activeCall->id,
            ]);

            return false;
        }

        if ($activeCall) {
            $this->endOrphanCall($activeCall, $callManager);
        }

        $appointmentService->end($appointment);

        Log::info('STUCK_IN_PROGRESS_APPOINTMENT_ENDED', [
            'appointment_id' => $appointment->id,
            'call_id' => $activeCall?->id,
            'minutes_past_window' => (int) $appointment->inProgressWindowEndsAt()->diffInMinutes(now()),
        ]);

        return true;
    }

    private function endOrphanCall(Call $call, CallManagerService $callManager): void
    {
        if ($call->call_type === Call::TYPE_SCHEDULED) {
            $callManager->endCallForAppointmentWindow($call);

            return;
        }

        if ($call->room) {
            try {
                $callManager->destroyRoom($call->room);
            } catch (\Throwable $exception) {
                Log::warning('STUCK_IN_PROGRESS_ROOM_DESTROY_FAILED', [
                    'call_id' => $call->id,
                    'room_id' => $call->room->room_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $call->updateFromSystem([
            'status' => Call::STATUS_ENDED,
            'ended_at' => now(),
            'call_closed_reason' => Call::CLOSED_REASON_WINDOW_EXPIRED,
        ]);
    }
}
