<?php

namespace App\Jobs;

use App\Models\Call;
use App\Services\CallManagerService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Encerra salas scheduled ativas que ficaram sem presença (RF-04):
 *  - ROOM_INACTIVE: nenhum participante reportou presença há room_inactive_minutes.
 *  - DOCTOR_DISCONNECTED: paciente presente, mas o médico caiu e não voltou
 *    dentro de doctor_reconnect_grace_minutes.
 *
 * A presença é alimentada pelo heartbeat POST /calls/{id}/presence enquanto o SFU
 * está conectado, e marcada no join via AppointmentVideoSessionController.
 */
class EndEmptyVideoCalls implements ShouldQueue
{
    use Queueable;

    public function handle(CallManagerService $callManager): void
    {
        $inactiveMinutes = (int) config('telemedicine.video_call.room_inactive_minutes', 15);
        $graceMinutes = (int) config('telemedicine.video_call.doctor_reconnect_grace_minutes', 2);
        $inactiveCutoff = Carbon::now()->subMinutes($inactiveMinutes);
        $graceCutoff = Carbon::now()->subMinutes($graceMinutes);
        $ended = 0;

        Call::query()
            ->with(['room', 'doctor', 'patient'])
            ->where('call_type', Call::TYPE_SCHEDULED)
            ->where('status', Call::STATUS_ACCEPTED)
            ->whereNull('ended_at')
            // Sala que já foi usada por ao menos um participante.
            ->where(function ($q) {
                $q->whereNotNull('doctor_joined_at')->orWhereNotNull('patient_joined_at');
            })
            ->orderBy('accepted_at')
            ->chunkById(50, function ($calls) use ($callManager, $inactiveCutoff, $graceCutoff, &$ended) {
                foreach ($calls as $call) {
                    $reason = $this->resolveReason($call, $inactiveCutoff, $graceCutoff);

                    if (! $reason) {
                        continue;
                    }

                    try {
                        $callManager->endCallSystem($call, $reason);
                        $ended++;
                    } catch (\Throwable $e) {
                        Log::warning('END_EMPTY_CALL_FAILED', ['call_id' => $call->id, 'error' => $e->getMessage()]);
                    }
                }
            });

        if ($ended > 0) {
            Log::info('EMPTY_VIDEO_CALLS_ENDED', [
                'ended_count' => $ended,
                'inactive_minutes' => $inactiveMinutes,
                'grace_minutes' => $graceMinutes,
            ]);
        }
    }

    private function resolveReason(Call $call, Carbon $inactiveCutoff, Carbon $graceCutoff): ?string
    {
        $doctorSeen = $call->doctor_last_seen_at;
        $patientSeen = $call->patient_last_seen_at;

        $doctorActive = $doctorSeen && $doctorSeen->greaterThan($inactiveCutoff);
        $patientActive = $patientSeen && $patientSeen->greaterThan($inactiveCutoff);

        // Sala vazia: ninguém presente há inactiveMinutes.
        if (! $doctorActive && ! $patientActive) {
            return Call::CLOSED_REASON_ROOM_INACTIVE;
        }

        // Médico caiu: paciente segue na sala, médico já esteve presente mas
        // ultrapassou a tolerância de reconexão.
        if ($patientActive && $doctorSeen && $doctorSeen->lessThan($graceCutoff)) {
            return Call::CLOSED_REASON_DOCTOR_DISCONNECTED;
        }

        return null;
    }
}
