<?php

namespace App\Jobs;

use App\Models\Call;
use App\Services\CallManagerService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EndScheduledVideoCalls implements ShouldQueue
{
    use Queueable;

    public function handle(CallManagerService $callManager): void
    {
        $trailingMinutes = (int) config('telemedicine.video_call.window_trailing_minutes', 10);
        $now = Carbon::now();
        $ended = 0;

        // Encerra scheduled calls cujo appointment.scheduled_at + trailing < now
        Call::query()
            ->with(['room', 'appointment'])
            ->where('call_type', Call::TYPE_SCHEDULED)
            ->whereIn('status', [Call::STATUS_ACCEPTED])
            ->whereNull('ended_at')
            ->whereHas('appointment', function ($q) use ($now, $trailingMinutes) {
                $q->whereNotNull('scheduled_at')
                    ->whereRaw('scheduled_at + interval \''.$trailingMinutes.' minutes\' < ?', [$now]);
            })
            ->orderBy('accepted_at')
            ->chunkById(50, function ($calls) use ($callManager, &$ended) {
                foreach ($calls as $call) {
                    try {
                        $callManager->endCallForAppointmentWindow($call);
                        $ended++;
                    } catch (\Throwable $e) {
                        Log::warning('END_SCHEDULED_CALL_FAILED', [
                            'call_id' => $call->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        if ($ended > 0) {
            Log::info('SCHEDULED_VIDEO_CALLS_ENDED', [
                'ended_count' => $ended,
                'trailing_minutes' => $trailingMinutes,
            ]);
        }
    }
}
