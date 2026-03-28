<?php

namespace App\Services;

use App\Contracts\MediaGatewayInterface;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Centraliza estado da chamada, integração com SFU/Media Gateway e persistência.
 * Call (negócio) vs Room (mídia) conforme doc IMPLEMENTACAO_SFU_MEDIASOUP.md.
 */
class CallManagerService
{
    public function __construct(
        protected MediaGatewayInterface $mediaGateway,
        protected AppointmentService $appointmentService
    ) {}

    /**
     * Inicia solicitação de chamada (request).
     * Valida appointment e participante; persiste Call; emite evento; inicia timeout (job em task posterior).
     */
    public function createCall(Appointments $appointment, User $caller): Call
    {
        $doctorId = $appointment->doctor_id;
        $patientId = $appointment->patient_id;
        $callerDoctor = $caller->doctor?->id;
        $callerPatient = $caller->patient?->id;

        if (!$callerDoctor && !$callerPatient) {
            throw new \InvalidArgumentException('Usuário não é médico nem paciente desta consulta.');
        }
        if ($callerDoctor !== $doctorId && $callerPatient !== $patientId) {
            throw new \InvalidArgumentException('Usuário não é participante desta consulta.');
        }

        $call = Call::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'status' => Call::STATUS_REQUESTED,
            'requested_at' => now(),
        ]);

        $calleeUserId = $this->getCalleeUserId($call, $caller);
        event(new \App\Events\VideoCallRequested($call, $caller, $calleeUserId));

        Log::info('CALL_CREATED', [
            'call_id' => $call->id,
            'appointment_id' => $appointment->id,
            'user_id' => $caller->id,
        ]);

        return $call;
    }

    /**
     * Aceita a chamada: cria sala no SFU (via Gateway), persiste Room, gera token (JWT em task posterior), emite evento.
     *
     * @return array{call: Call, room: Room, token: string, sfu_ws_url: string}
     */
    public function acceptCall(Call $call, User $acceptedBy): array
    {
        if (!in_array($call->status, [Call::STATUS_REQUESTED, Call::STATUS_RINGING], true)) {
            throw new \InvalidArgumentException('Chamada não está em estado solicitado ou tocando.');
        }

        $calleeUserId = $this->getCalleeUserId($call, $acceptedBy);
        if ((string) $acceptedBy->id !== (string) $calleeUserId) {
            throw new \InvalidArgumentException('Apenas o destinatário da chamada pode aceitar.');
        }

        $room = $this->createRoom($call);

        $call->update([
            'status' => Call::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        if ($call->appointment && $call->appointment->status !== Appointments::STATUS_IN_PROGRESS) {
            $this->appointmentService->start($call->appointment, $acceptedBy->id);
        }

        $token = $this->generateRoomToken($call, $room, $acceptedBy);
        $sfuWsUrl = config('services.media_gateway.sfu_ws_url', env('SFU_WS_URL', ''));

        $call->load(['doctor', 'patient']);
        event(new \App\Events\VideoCallAccepted(
            $call,
            $token,
            $sfuWsUrl,
            (string) $call->doctor->user_id,
            (string) $call->patient->user_id
        ));

        Log::info('CALL_ACCEPTED', [
            'call_id' => $call->id,
            'room_id' => $room->room_id,
            'user_id' => $acceptedBy->id,
            'appointment_id' => $call->appointment_id,
        ]);

        return [
            'call' => $call,
            'room' => $room,
            'token' => $token,
            'sfu_ws_url' => $sfuWsUrl,
        ];
    }

    /**
     * Rejeita a chamada: atualiza estado e emite evento.
     */
    public function rejectCall(Call $call, User $rejectedBy): void
    {
        if (!in_array($call->status, [Call::STATUS_REQUESTED, Call::STATUS_RINGING], true)) {
            throw new \InvalidArgumentException('Chamada não está em estado solicitado ou tocando.');
        }

        $call->update(['status' => Call::STATUS_REJECTED]);
        event(new \App\Events\VideoCallRejected($call, $rejectedBy));
    }

    /**
     * Encerra a chamada: atualiza consulta, emite evento e destrói sala no SFU.
     */
    public function endCall(Call $call, User $endedBy): void
    {
        if (!in_array($call->status, [Call::STATUS_ACCEPTED, Call::STATUS_REQUESTED, Call::STATUS_RINGING], true)) {
            throw new \InvalidArgumentException('Chamada não está ativa.');
        }

        $call->update([
            'status' => Call::STATUS_ENDED,
            'ended_at' => now(),
        ]);

        if ($call->appointment && $call->appointment->status === Appointments::STATUS_IN_PROGRESS) {
            $this->appointmentService->end($call->appointment, $endedBy->id);
        }

        $room = $call->room;
        if ($room) {
            $this->destroyRoom($room);
        }

        event(new \App\Events\VideoCallEnded($call, $endedBy));

        Log::info('CALL_ENDED', [
            'call_id' => $call->id,
            'room_id' => $room?->room_id,
            'user_id' => $endedBy->id,
            'appointment_id' => $call->appointment_id,
        ]);
    }

    /**
     * Delega ao Media Gateway: criar sala no SFU; persiste e retorna Room.
     */
    public function createRoom(Call $call): Room
    {
        $result = $this->mediaGateway->createRoom($call->id);

        $room = Room::create([
            'call_id' => $call->id,
            'room_id' => $result['room_id'],
            'sfu_node' => $result['sfu_node'] ?? null,
        ]);

        Log::info('ROOM_CREATED', [
            'call_id' => $call->id,
            'room_id' => $room->room_id,
        ]);

        return $room;
    }

    /**
     * Notifica Media Gateway/SFU para fechar a sala.
     */
    public function destroyRoom(Room $room): void
    {
        $this->mediaGateway->destroyRoom($room->room_id);

        Log::info('ROOM_LEFT', [
            'room_id' => $room->room_id,
            'call_id' => $room->call_id,
        ]);
    }

    /**
     * Retorna o user_id do destinatário da chamada (quem recebe o request).
     */
    protected function getCalleeUserId(Call $call, User $caller): string
    {
        $callerDoctorId = $caller->doctor?->id;
        $callerPatientId = $caller->patient?->id;

        if ($callerDoctorId === $call->doctor_id) {
            return (string) $call->patient->user_id;
        }
        if ($callerPatientId === $call->patient_id) {
            return (string) $call->doctor->user_id;
        }

        throw new \InvalidArgumentException('Usuário não é participante desta chamada.');
    }

    /**
     * Gera token de acesso à sala (JWT HS256).
     * Payload: callId, roomId, userId, role, exp.
     */
    protected function generateRoomToken(Call $call, Room $room, User $user): string
    {
        $secret = config('services.media_gateway.jwt_secret', env('SFU_JWT_SECRET'));

        if (!$secret) {
            throw new \RuntimeException('JWT secret não configurado (SFU_JWT_SECRET ou services.media_gateway.jwt_secret).');
        }

        $ttlMinutes = (int) config('telemedicine.video_call.token_ttl_minutes', 5);
        $now = time();
        $exp = $now + ($ttlMinutes * 60);

        $role = $user->doctor ? 'doctor' : ($user->patient ? 'patient' : 'user');

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'callId' => (string) $call->id,
            'roomId' => (string) $room->room_id,
            'userId' => (string) $user->id,
            'role' => $role,
            'iat' => $now,
            'exp' => $exp,
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];

        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
