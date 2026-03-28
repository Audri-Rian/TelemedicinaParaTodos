<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCallAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Call $call,
        public string $token,
        public string $sfuWsUrl,
        public string $doctorUserId,
        public string $patientUserId
    ) {
        $this->call->loadMissing(['appointment', 'room']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("video-call.{$this->doctorUserId}"),
            new PrivateChannel("video-call.{$this->patientUserId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'VideoCallAccepted';
    }

    /**
     * roomId só dentro do token; frontend não recebe roomId como dado confiável.
     */
    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'token' => $this->token,
            'sfu_ws_url' => $this->sfuWsUrl,
        ];
    }
}
