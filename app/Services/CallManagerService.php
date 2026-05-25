<?php

namespace App\Services;

use App\Contracts\MediaGatewayInterface;
use App\DataTransferObjects\MediaRoomData;
use App\Events\VideoCallAvailable;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallManagerService
{
    public function __construct(
        protected MediaGatewayInterface $mediaGateway,
        protected AppointmentService $appointmentService
    ) {}

    /**
     * Provisiona sala scheduled para appointment — idempotente.
     * Lock Redis evita provisioning duplo em execuções concorrentes do job.
     *
     * @return array{call: Call, created: bool}
     */
    public function provisionAppointmentCall(Appointments $appointment): array
    {
        $lockKey = "video_call_lock:{$appointment->id}";
        $lock = Cache::lock($lockKey, 30);

        if (! $lock->get()) {
            Log::debug('VIDEO_CALL_PROVISION_SKIPPED', ['appointment_id' => $appointment->id, 'reason' => 'lock_not_acquired']);
            throw new \RuntimeException('Provisioning já em andamento para esta consulta.');
        }

        try {
            return DB::transaction(function () use ($appointment) {
                $existing = Call::where('appointment_id', $appointment->id)
                    ->where('call_type', Call::TYPE_SCHEDULED)
                    ->whereNull('ended_at')
                    ->whereIn('status', [Call::STATUS_ACCEPTED])
                    ->with('room')
                    ->lockForUpdate()
                    ->first();

                if ($existing?->room) {
                    Log::debug('VIDEO_CALL_PROVISION_SKIPPED', ['appointment_id' => $appointment->id, 'call_id' => $existing->id]);

                    return ['call' => $existing, 'created' => false];
                }

                $leadMinutes = (int) config('telemedicine.video_call.window_lead_minutes', 10);

                if ($existing && ! $existing->room) {
                    $room = $this->createRoom($existing);
                    $existing->update(['accepted_at' => now()]);

                    $this->broadcastAvailable($existing, $appointment);
                    Log::info('VIDEO_CALL_PROVISIONED', ['appointment_id' => $appointment->id, 'call_id' => $existing->id, 'room_id' => $room->room_id, 'call_type' => 'scheduled']);

                    return ['call' => $existing, 'created' => false];
                }

                $call = Call::create([
                    'call_type' => Call::TYPE_SCHEDULED,
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                    'status' => Call::STATUS_ACCEPTED,
                    'requested_at' => $appointment->scheduled_at->copy()->subMinutes($leadMinutes),
                    'accepted_at' => now(),
                ]);

                $this->createRoom($call);
                $this->broadcastAvailable($call, $appointment);

                Log::info('VIDEO_CALL_PROVISIONED', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $call->id,
                    'call_type' => 'scheduled',
                ]);

                return ['call' => $call, 'created' => true];
            });
        } catch (\Throwable $e) {
            Log::error('VIDEO_CALL_PROVISION_FAILED', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Inicia chamada ad-hoc (paciente → médico).
     * Valida relacionamento (consulta nos últimos N dias) antes de criar.
     */
    public function createCall(User $patient, Doctor $doctor): Call
    {
        $patientId = $patient->patient?->id;

        if (! $patientId) {
            throw new \InvalidArgumentException('Usuário não é paciente.');
        }

        $relationshipDays = (int) config('telemedicine.video_call.ad_hoc_relationship_days', 7);

        $hasRelationship = Appointments::where('doctor_id', $doctor->id)
            ->where('patient_id', $patientId)
            ->whereNotIn('status', ['cancelled'])
            ->where('ended_at', '>=', now()->subDays($relationshipDays))
            ->exists();

        if (! $hasRelationship) {
            Log::warning('VIDEO_CALL_ADHOC_UNAUTHORIZED', ['patient_id' => $patientId, 'doctor_id' => $doctor->id]);
            throw new \InvalidArgumentException('Você não tem consulta com este médico nos últimos '.$relationshipDays.' dias.');
        }

        $call = Call::create([
            'call_type' => Call::TYPE_AD_HOC,
            'appointment_id' => null,
            'doctor_id' => $doctor->id,
            'patient_id' => $patientId,
            'status' => Call::STATUS_REQUESTED,
            'requested_at' => now(),
        ]);

        event(new \App\Events\VideoCallRequested($call, $patient, (string) $doctor->user_id));

        Log::info('VIDEO_CALL_ADHOC_REQUESTED', [
            'call_id' => $call->id,
            'patient_id' => $patientId,
            'doctor_id' => $doctor->id,
        ]);

        return $call;
    }

    /**
     * Aceita chamada ad-hoc: cria room, gera token, emite evento.
     *
     * @return array{call: Call, room: Room, token: string, sfu_ws_url: string}
     */
    public function acceptCall(Call $call, User $acceptedBy): array
    {
        if ($call->call_type !== Call::TYPE_AD_HOC) {
            throw new \InvalidArgumentException('Apenas chamadas ad-hoc podem ser aceitas manualmente.');
        }

        if (! in_array($call->status, [Call::STATUS_REQUESTED, Call::STATUS_RINGING], true)) {
            throw new \InvalidArgumentException('Chamada não está em estado solicitado ou tocando.');
        }

        $call->loadMissing(['doctor.user', 'patient.user']);

        if ((string) $acceptedBy->id !== (string) $call->doctor->user_id) {
            throw new \InvalidArgumentException('Apenas o médico da chamada pode aceitá-la.');
        }

        $room = $this->createRoom($call);

        $call->update([
            'status' => Call::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        $token = $this->generatePublicRoomToken($call, $room, $acceptedBy);
        $sfuWsUrl = $room->media_ws_url ?? config('services.media_gateway.sfu_ws_url', '') ?: null;

        event(new \App\Events\VideoCallAccepted(
            $call,
            $token,
            $sfuWsUrl,
            (string) $call->doctor->user_id,
            (string) $call->patient->user_id
        ));

        Log::info('VIDEO_CALL_ADHOC_ACCEPTED', [
            'call_id' => $call->id,
            'room_id' => $room->room_id,
        ]);

        return [
            'call' => $call,
            'room' => $room,
            'token' => $token,
            'sfu_ws_url' => $sfuWsUrl,
        ];
    }

    /**
     * Rejeita chamada ad-hoc.
     */
    public function rejectCall(Call $call, User $rejectedBy): void
    {
        if ($call->call_type !== Call::TYPE_AD_HOC) {
            throw new \InvalidArgumentException('Apenas chamadas ad-hoc podem ser recusadas manualmente.');
        }

        if (! in_array($call->status, [Call::STATUS_REQUESTED, Call::STATUS_RINGING], true)) {
            throw new \InvalidArgumentException('Chamada não está em estado solicitado ou tocando.');
        }

        $call->update(['status' => Call::STATUS_REJECTED]);
        event(new \App\Events\VideoCallRejected($call, $rejectedBy));

        Log::info('VIDEO_CALL_ADHOC_REJECTED', ['call_id' => $call->id]);
    }

    /**
     * Encerra chamada voluntariamente (ambos os tipos).
     * Para scheduled: scheduler também encerra por janela — este método é complementar.
     */
    public function endCall(Call $call, User $endedBy): void
    {
        if (! in_array($call->status, [Call::STATUS_ACCEPTED, Call::STATUS_REQUESTED, Call::STATUS_RINGING], true)) {
            throw new \InvalidArgumentException('Chamada não está ativa.');
        }

        $call->update([
            'status' => Call::STATUS_ENDED,
            'ended_at' => now(),
        ]);

        // Não encerrar appointment em scheduled (D6) — apenas em ad-hoc
        if ($call->call_type === Call::TYPE_AD_HOC && $call->appointment && $call->appointment->status === Appointments::STATUS_IN_PROGRESS) {
            $this->appointmentService->end($call->appointment, $endedBy->id);
        }

        $room = $call->room;
        if ($room) {
            $this->destroyRoom($room);
        }

        event(new \App\Events\VideoCallEnded($call, $endedBy));

        Log::info('CALL_ENDED', [
            'call_id' => $call->id,
            'call_type' => $call->call_type,
            'room_id' => $room?->room_id,
            'user_id' => $endedBy->id,
        ]);
    }

    /**
     * Encerra chamada scheduled fora da janela (chamado por EndScheduledVideoCalls).
     */
    public function endCallForAppointmentWindow(Call $call): void
    {
        if ($call->call_type !== Call::TYPE_SCHEDULED) {
            return;
        }

        $room = $call->room;
        if ($room) {
            try {
                $this->destroyRoom($room);
            } catch (\Throwable $e) {
                Log::warning('VIDEO_CALL_ROOM_DESTROY_FAILED', ['call_id' => $call->id, 'error' => $e->getMessage()]);
            }
        }

        $call->update([
            'status' => Call::STATUS_ENDED,
            'ended_at' => now(),
        ]);

        Log::info('VIDEO_CALL_WINDOW_ENDED', [
            'call_id' => $call->id,
            'appointment_id' => $call->appointment_id,
        ]);
    }

    /**
     * Retorna a call ativa do usuário.
     * Prioridade: scheduled na janela > ad-hoc aceita (D13).
     */
    public function getActiveCallForUser(User $user): ?Call
    {
        $doctorId = $user->doctor?->id;
        $patientId = $user->patient?->id;

        if (! $doctorId && ! $patientId) {
            return null;
        }

        $baseQuery = fn ($type) => Call::with(['room', 'appointment'])
            ->where('call_type', $type)
            ->where(function ($q) use ($doctorId, $patientId) {
                if ($doctorId) {
                    $q->orWhere('doctor_id', $doctorId);
                }
                if ($patientId) {
                    $q->orWhere('patient_id', $patientId);
                }
            });

        // Scheduled na janela (status=accepted, sem ended_at)
        $scheduled = $baseQuery(Call::TYPE_SCHEDULED)
            ->where('status', Call::STATUS_ACCEPTED)
            ->whereNull('ended_at')
            ->latest('accepted_at')
            ->first();

        if ($scheduled) {
            return $scheduled;
        }

        // Ad-hoc aceita ou em andamento
        return $baseQuery(Call::TYPE_AD_HOC)
            ->whereIn('status', [Call::STATUS_REQUESTED, Call::STATUS_RINGING, Call::STATUS_ACCEPTED])
            ->latest('requested_at')
            ->first();
    }

    public function createRoom(Call $call): Room
    {
        /** @var MediaRoomData $roomData */
        $roomData = $this->mediaGateway->createRoom($call->id);

        $room = Room::create([
            'call_id' => $call->id,
            'room_id' => $roomData->roomId,
            'sfu_node' => $roomData->sfuNode,
            'media_ws_url' => $roomData->mediaWsUrl,
        ]);

        Log::info('ROOM_CREATED', ['call_id' => $call->id, 'room_id' => $room->room_id]);

        return $room;
    }

    public function destroyRoom(Room $room): void
    {
        $this->mediaGateway->destroyRoom($room->room_id);

        Log::info('ROOM_LEFT', ['room_id' => $room->room_id, 'call_id' => $room->call_id]);
    }

    public function generatePublicRoomToken(Call $call, Room $room, User $user): string
    {
        $secret = config('services.media_gateway.jwt_secret');

        if (! $secret) {
            throw new \RuntimeException('JWT secret não configurado (SFU_JWT_SECRET ou services.media_gateway.jwt_secret).');
        }

        $ttlMinutes = (int) config('telemedicine.video_call.token_ttl_minutes', 10);
        $now = time();
        $exp = $now + ($ttlMinutes * 60);

        $role = $user->doctor ? 'doctor' : ($user->patient ? 'patient' : 'user');

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
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

    private function broadcastAvailable(Call $call, Appointments $appointment): void
    {
        $call->loadMissing(['doctor.user', 'patient.user']);

        event(new VideoCallAvailable(
            $call,
            (string) $call->doctor->user_id,
            (string) $call->patient->user_id,
        ));
    }
}
