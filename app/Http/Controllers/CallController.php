<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCallRequest;
use App\Models\Call;
use App\Models\Doctor;
use App\Services\CallManagerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{
    public function __construct(protected CallManagerService $callManager) {}

    public function store(StoreCallRequest $request): JsonResponse
    {
        $doctor = Doctor::findOrFail($request->validated('doctor_id'));

        $this->authorize('video-call-request-adhoc', $doctor);

        try {
            $call = DB::transaction(function () use ($request, $doctor) {
                return $this->callManager->createCall($request->user(), $doctor);
            });
        } catch (\InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'não tem consulta') ? 403 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }

        return response()->json([
            'message' => 'Chamada ad-hoc criada com sucesso',
            'data' => ['call_id' => $call->id],
        ], 201);
    }

    public function accept(Call $call): JsonResponse
    {
        if ($call->call_type === Call::TYPE_SCHEDULED) {
            return response()->json(['message' => 'Chamadas agendadas não requerem aceite manual.'], 403);
        }

        $this->authorize('video-call-accept', $call);

        try {
            $result = DB::transaction(function () use ($call) {
                $call = Call::lockForUpdate()->findOrFail($call->id);

                return $this->callManager->acceptCall($call, request()->user());
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            Log::error('Falha ao gerar token JWT da sala', ['call_id' => $call->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Erro interno ao processar a chamada'], 500);
        } catch (\Exception $e) {
            Log::error('Falha ao criar sala no SFU', ['call_id' => $call->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Falha ao criar sala de vídeo'], 502);
        }

        return response()->json([
            'message' => 'Chamada aceita',
            'data' => [
                'token' => $result['token'],
                'sfu_ws_url' => $result['sfu_ws_url'],
            ],
        ]);
    }

    public function reject(Call $call): JsonResponse
    {
        if ($call->call_type === Call::TYPE_SCHEDULED) {
            return response()->json(['message' => 'Chamadas agendadas não podem ser recusadas manualmente.'], 403);
        }

        $this->authorize('video-call-reject', $call);

        try {
            $this->callManager->rejectCall($call, request()->user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }

    public function end(Call $call): JsonResponse
    {
        $this->authorize('video-call-end', $call);

        try {
            $this->callManager->endCall($call, request()->user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }

    public function show(Call $call): JsonResponse
    {
        $this->authorize('video-call-view', $call);

        $call->loadMissing('room');

        return response()->json([
            'message' => 'Estado da chamada',
            'data' => [
                'call_id' => $call->id,
                'call_type' => $call->call_type,
                'status' => $call->status,
                'room_id' => $call->room?->room_id,
            ],
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        $this->authorize('video-call-view-active');

        $call = $this->callManager->getActiveCallForUser($request->user());

        if (! $call) {
            return response()->json(['data' => null], 204);
        }

        $user = $request->user();
        $role = $user->doctor ? 'doctor' : 'patient';
        $videoCallRoute = $role === 'doctor' ? route('doctor.video-call') : route('patient.video-call');

        $appointmentLabel = null;
        $window = null;

        if ($call->appointment) {
            $dt = $call->appointment->scheduled_at;
            $appointmentLabel = $dt ? Carbon::parse($dt)->format('d/m H:i') : null;

            if ($call->call_type === Call::TYPE_SCHEDULED && $dt) {
                $leadMinutes = (int) config('telemedicine.video_call.window_lead_minutes', 10);
                $trailingMinutes = (int) config('telemedicine.video_call.window_trailing_minutes', 10);
                $window = [
                    'opens_at' => Carbon::parse($dt)->subMinutes($leadMinutes)->toIso8601String(),
                    'closes_at' => Carbon::parse($dt)->addMinutes($trailingMinutes)->toIso8601String(),
                ];
            }
        }

        $token = null;
        if ($call->status === Call::STATUS_ACCEPTED && $call->room) {
            $tokenCacheTtl = max(15, ((int) config('telemedicine.video_call.token_ttl_minutes', 10) * 60) - 30);
            $tokenCacheKey = implode(':', [
                'video_call_active_token',
                (string) $call->id,
                (string) $user->id,
                (string) ($call->updated_at?->getTimestamp() ?? 0),
                (string) ($call->room->updated_at?->getTimestamp() ?? 0),
            ]);

            $token = Cache::get($tokenCacheKey);

            if (! is_string($token) || $token === '') {
                try {
                    $token = $this->callManager->generatePublicRoomToken($call, $call->room, $user);
                    Cache::put($tokenCacheKey, $token, now()->addSeconds($tokenCacheTtl));
                } catch (\Throwable) {
                    $token = null;
                    // Token generation fail: client will re-enter on next poll
                }
            }
        }

        return response()->json([
            'data' => [
                'call_id' => $call->id,
                'call_type' => $call->call_type,
                'appointment_id' => $call->appointment_id,
                'status' => $call->status,
                'role' => $role,
                'token' => $token,
                'sfu_ws_url' => $call->room?->media_ws_url,
                'video_call_route' => $videoCallRoute,
                'appointment_label' => $appointmentLabel,
                'window' => $window,
            ],
        ]);
    }
}
