<?php

namespace App\Http\Controllers;

use App\Models\Appointments;
use App\Services\CallManagerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentVideoSessionController extends Controller
{
    public function __construct(protected CallManagerService $callManager) {}

    /**
     * Provisiona (ou retorna) a sala de vídeo do appointment e emite token para o usuário.
     * Idempotente: chamadas repetidas retornam a mesma Call enquanto dentro da janela.
     */
    public function store(Request $request, Appointments $appointment): JsonResponse
    {
        $this->authorize('video-call-join-session', $appointment);

        $user = $request->user();

        Log::debug('[VIDEO_CALL] POST /video/session — provision start', [
            'appointment_id' => $appointment->id,
            'user_id' => $user->id,
            'trigger' => 'client_join_or_reconnect',
        ]);

        try {
            ['call' => $call] = $this->callManager->provisionAppointmentCall($appointment);
        } catch (\RuntimeException $e) {
            Log::warning('[VIDEO_CALL] Provision lock não adquirido (concurrent)', [
                'appointment_id' => $appointment->id,
            ]);

            return response()->json(['message' => 'Sala em provisioning, tente novamente em instantes.'], 503);
        } catch (\Throwable $e) {
            Log::error('[VIDEO_CALL] APPOINTMENT_VIDEO_SESSION_FAILED', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Falha ao provisionar sala de vídeo.'], 502);
        }

        $call->loadMissing('room');

        if (! $call->room) {
            Log::error('[VIDEO_CALL] Room não encontrada após provision', [
                'call_id' => $call->id,
                'appointment_id' => $appointment->id,
            ]);

            return response()->json(['message' => 'Sala ainda não disponível, tente novamente.'], 503);
        }

        Log::debug('[VIDEO_CALL] Room provisionada — gerando token', [
            'call_id' => $call->id,
            'room_id' => $call->room->room_id,
            'user_id' => $user->id,
        ]);

        try {
            $token = $this->callManager->generatePublicRoomToken($call, $call->room, $user);
        } catch (\RuntimeException $e) {
            Log::error('[VIDEO_CALL] Falha ao gerar token na session', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Configuração de token ausente no servidor.'], 500);
        }

        $role = $user->doctor ? 'doctor' : 'patient';

        if ($role === 'doctor' && ! $call->doctor_joined_at) {
            $call->update(['doctor_joined_at' => now()]);
        } elseif ($role === 'patient' && ! $call->patient_joined_at) {
            $call->update(['patient_joined_at' => now()]);
        }

        $leadMinutes = (int) config('telemedicine.video_call.window_lead_minutes', 10);
        $trailingMinutes = (int) config('telemedicine.video_call.window_trailing_minutes', 10);
        $scheduledAt = Carbon::parse($appointment->scheduled_at);

        return response()->json([
            'data' => [
                'call_id' => $call->id,
                'room_id' => $call->room->room_id,
                'role' => $role,
                'token' => $token,
                'sfu_ws_url' => $call->room->media_ws_url,
                'window' => [
                    'opens_at' => $scheduledAt->copy()->subMinutes($leadMinutes)->toIso8601String(),
                    'closes_at' => $scheduledAt->copy()->addMinutes($trailingMinutes)->toIso8601String(),
                ],
            ],
        ]);
    }
}
