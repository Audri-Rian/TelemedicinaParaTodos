<?php

namespace App\Events;

use App\Models\Call;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCallEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Call $call,
        public ?User $endedBy = null,
        public ?string $reason = null
    ) {
        $this->call->loadMissing(['doctor', 'patient']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('video-call.'.(string) $this->call->doctor->user_id),
            new PrivateChannel('video-call.'.(string) $this->call->patient->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'VideoCallEnded';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'reason' => $this->reason,
            'ended_by_role' => $this->endedByRole(),
            'message_key' => $this->messageKey(),
        ];
    }

    private function endedByRole(): string
    {
        if (! $this->endedBy) {
            return 'system';
        }

        if ($this->endedBy->doctor && (string) $this->endedBy->doctor->id === (string) $this->call->doctor_id) {
            return 'doctor';
        }

        if ($this->endedBy->patient && (string) $this->endedBy->patient->id === (string) $this->call->patient_id) {
            return 'patient';
        }

        return 'system';
    }

    private function messageKey(): string
    {
        return match ($this->reason) {
            Call::CLOSED_REASON_ENDED_BY_DOCTOR => 'call.ended.by_doctor',
            Call::CLOSED_REASON_WINDOW_EXPIRED,
            Call::CLOSED_REASON_NO_SHOW,
            Call::CLOSED_REASON_DOCTOR_NO_SHOW,
            Call::CLOSED_REASON_PATIENT_NO_SHOW => 'call.ended.time_expired',
            Call::CLOSED_REASON_ROOM_INACTIVE => 'call.ended.inactivity',
            Call::CLOSED_REASON_DOCTOR_DISCONNECTED => 'call.ended.doctor_unavailable',
            default => 'call.ended.generic',
        };
    }
}
