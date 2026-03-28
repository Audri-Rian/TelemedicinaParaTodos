<?php

namespace App\Events;

use App\Models\Call;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCallRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Call $call,
        public User $caller,
        public string $calleeUserId
    ) {
        $this->call->loadMissing(['appointment', 'doctor', 'patient']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("video-call.{$this->calleeUserId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'VideoCallRequested';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'appointment_id' => $this->call->appointment_id,
            'caller' => [
                'id' => $this->caller->id,
                'name' => $this->caller->name,
            ],
        ];
    }
}
