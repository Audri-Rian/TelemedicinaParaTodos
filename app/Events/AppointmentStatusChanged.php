<?php

namespace App\Events;

use App\Models\Appointments;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Appointments $appointment)
    {
        $this->appointment->loadMissing([
            'doctor.user',
            'patient.user',
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("appointment.{$this->appointment->doctor_id}"),
            new PrivateChannel("appointment.{$this->appointment->patient_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'appointment' => [
                'id' => $this->appointment->id,
                'status' => $this->appointment->status,
                'doctor_id' => $this->appointment->doctor_id,
                'patient_id' => $this->appointment->patient_id,
                'scheduled_at' => optional($this->appointment->scheduled_at)->toIso8601String(),
                'started_at' => optional($this->appointment->started_at)->toIso8601String(),
                'ended_at' => optional($this->appointment->ended_at)->toIso8601String(),
            ],
        ];
    }
}
