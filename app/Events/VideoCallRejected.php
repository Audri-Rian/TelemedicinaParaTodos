<?php

namespace App\Events;

use App\Models\Call;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCallRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Call $call,
        public User $rejectedBy
    ) {
        $this->call->loadMissing(['doctor', 'patient']);
    }

    public function broadcastOn(): array
    {
        $doctorUserId = (string) $this->call->doctor->user_id;
        $patientUserId = (string) $this->call->patient->user_id;
        $rejectedById = (string) $this->rejectedBy->id;
        $callerUserId = $rejectedById === $doctorUserId ? $patientUserId : $doctorUserId;
        return [
            new PrivateChannel("video-call.{$callerUserId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'VideoCallRejected';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
        ];
    }
}
