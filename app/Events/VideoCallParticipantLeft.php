<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCallParticipantLeft implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  'doctor'|'patient'  $role  Papel de quem saiu da chamada.
     */
    public function __construct(
        public Call $call,
        public string $role,
        public string $messageKey
    ) {
        $this->call->loadMissing(['doctor', 'patient']);
    }

    /**
     * Notifica apenas o participante remanescente (o peer de quem saiu).
     */
    public function broadcastOn(): array
    {
        $remainingUserId = $this->role === 'doctor'
            ? (string) $this->call->patient->user_id
            : (string) $this->call->doctor->user_id;

        return [new PrivateChannel('video-call.'.$remainingUserId)];
    }

    public function broadcastAs(): string
    {
        return 'VideoCallParticipantLeft';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'role' => $this->role,
            'message_key' => $this->messageKey,
        ];
    }
}
