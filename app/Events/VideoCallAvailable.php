<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCallAvailable implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Call $call,
        public string $doctorUserId,
        public string $patientUserId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("video-call.{$this->doctorUserId}"),
            new PrivateChannel("video-call.{$this->patientUserId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'VideoCallAvailable';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'appointment_id' => $this->call->appointment_id,
            'call_type' => $this->call->call_type,
        ];
    }
}
