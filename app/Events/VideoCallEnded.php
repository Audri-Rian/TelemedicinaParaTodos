<?php

namespace App\Events;

use App\Models\Call;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCallEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Call $call,
        public User $endedBy
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
        ];
    }
}
