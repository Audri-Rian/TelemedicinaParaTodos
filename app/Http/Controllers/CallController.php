<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCallRequest;
use App\Models\Appointments;
use App\Models\Call;
use App\Services\CallManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{
    public function __construct(protected CallManagerService $callManager) {}

    public function store(StoreCallRequest $request): JsonResponse
    {
        $appointment = Appointments::findOrFail($request->validated('appointment_id'));

        $this->authorize('video-call-request', $appointment);

        try {
            ['call' => $call, 'existing' => $existing] = DB::transaction(function () use ($appointment, $request) {
                $appointment = Appointments::lockForUpdate()->findOrFail($appointment->id);

                $existingCall = Call::where('appointment_id', $appointment->id)
                    ->whereIn('status', [Call::STATUS_REQUESTED, Call::STATUS_RINGING, Call::STATUS_ACCEPTED])
                    ->first();

                if ($existingCall) {
                    return ['call' => $existingCall, 'existing' => true];
                }

                return ['call' => $this->callManager->createCall($appointment, $request->user()), 'existing' => false];
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($existing) {
            return response()->json([
                'message' => 'Chamada já em andamento para esta consulta',
                'data' => ['call_id' => $call->id],
            ], 409);
        }

        return response()->json([
            'message' => 'Chamada criada com sucesso',
            'data' => ['call_id' => $call->id],
        ], 201);
    }

    public function accept(Call $call): JsonResponse
    {
        $this->authorize('video-call-accept', $call->appointment);

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
        $this->authorize('video-call-reject', $call->appointment);

        try {
            $this->callManager->rejectCall($call, request()->user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }

    public function end(Call $call): JsonResponse
    {
        $this->authorize('video-call-end', $call->appointment);

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
        if ($call->appointment) {
            $dt = $call->appointment->scheduled_at;
            $appointmentLabel = $dt ? \Carbon\Carbon::parse($dt)->format('d/m H:i') : null;
        }

        $token = null;
        if ($call->status === Call::STATUS_ACCEPTED && $call->room) {
            try {
                $token = $this->callManager->generatePublicRoomToken($call, $call->room, $user);
            } catch (\Throwable) {
                // Token generation fail: omit silently, client will re-enter
            }
        }

        return response()->json([
            'data' => [
                'call_id' => $call->id,
                'appointment_id' => $call->appointment_id,
                'status' => $call->status,
                'role' => $role,
                'token' => $token,
                'sfu_ws_url' => $call->room?->media_ws_url,
                'video_call_route' => $videoCallRoute,
                'appointment_label' => $appointmentLabel,
            ],
        ]);
    }
}
