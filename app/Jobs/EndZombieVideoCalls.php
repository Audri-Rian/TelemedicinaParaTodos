<?php

namespace App\Jobs;

use App\Models\Appointments;
use App\Models\Call;
use App\Services\AppointmentService;
use App\Services\CallManagerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EndZombieVideoCalls implements ShouldQueue
{
    use Queueable;

    public function handle(
        AppointmentService $appointmentService,
        CallManagerService $callManager
    ): void {
        $inactiveMinutes = (int) config('telemedicine.video_call.room_inactive_minutes', 60);
        $maxDurationMinutes = (int) config('telemedicine.video_call.room_max_duration_minutes', 120);
        $inactiveCutoff = now()->subMinutes($inactiveMinutes);
        $maxDurationCutoff = now()->subMinutes($maxDurationMinutes);
        $ended = 0;
        $missed = 0;

        Call::query()
            ->with(['appointment', 'room'])
            ->whereIn('status', [Call::STATUS_REQUESTED, Call::STATUS_RINGING, Call::STATUS_ACCEPTED])
            ->where(function ($query) use ($inactiveCutoff, $maxDurationCutoff) {
                $query
                    ->where(function ($query) use ($inactiveCutoff) {
                        $query->whereIn('status', [Call::STATUS_REQUESTED, Call::STATUS_RINGING])
                            ->where('requested_at', '<=', $inactiveCutoff);
                    })
                    ->orWhere(function ($query) use ($maxDurationCutoff) {
                        $query->where('status', Call::STATUS_ACCEPTED)
                            ->where('accepted_at', '<=', $maxDurationCutoff);
                    });
            })
            ->orderBy('created_at')
            ->chunkById(50, function ($calls) use ($appointmentService, $callManager, &$ended, &$missed) {
                foreach ($calls as $call) {
                    if ($call->status === Call::STATUS_ACCEPTED) {
                        $this->endAcceptedCall($call, $appointmentService, $callManager);
                        $ended++;

                        continue;
                    }

                    $call->update([
                        'status' => Call::STATUS_MISSED,
                        'ended_at' => now(),
                    ]);
                    $missed++;
                }
            });

        if ($ended > 0 || $missed > 0) {
            Log::info('ZOMBIE_VIDEO_CALLS_CLOSED', [
                'ended_count' => $ended,
                'missed_count' => $missed,
                'inactive_minutes' => $inactiveMinutes,
                'max_duration_minutes' => $maxDurationMinutes,
            ]);
        }
    }

    private function endAcceptedCall(
        Call $call,
        AppointmentService $appointmentService,
        CallManagerService $callManager
    ): void {
        if ($call->room) {
            try {
                $callManager->destroyRoom($call->room);
            } catch (\Throwable $exception) {
                Log::warning('ZOMBIE_VIDEO_ROOM_DESTROY_FAILED', [
                    'call_id' => $call->id,
                    'room_id' => $call->room->room_id,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        }

        $call->update([
            'status' => Call::STATUS_ENDED,
            'ended_at' => now(),
        ]);

        if ($call->appointment?->status === Appointments::STATUS_IN_PROGRESS) {
            $appointmentService->end($call->appointment);
        }
    }
}
