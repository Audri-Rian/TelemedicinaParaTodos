<?php

namespace Database\Factories;

use App\Models\Call;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class CallFactory extends Factory
{
    protected $model = Call::class;

    public function create($attributes = [], ?Model $parent = null)
    {
        return Model::unguarded(fn () => parent::create($attributes, $parent));
    }

    public function make($attributes = [], ?Model $parent = null)
    {
        return Model::unguarded(fn () => parent::make($attributes, $parent));
    }

    public function definition(): array
    {
        // Em seeders em massa, use Call::factory()->recycle($doctors)->recycle($patients).
        return [
            'call_type' => Call::TYPE_SCHEDULED,
            'appointment_id' => null,
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'status' => Call::STATUS_ACCEPTED,
            'requested_at' => Carbon::now()->subMinutes(5),
            'accepted_at' => Carbon::now()->subMinutes(5),
            'doctor_joined_at' => null,
            'patient_joined_at' => null,
            'ended_at' => null,
            'call_closed_reason' => null,
        ];
    }

    public function scheduled(): static
    {
        return $this->state([
            'call_type' => Call::TYPE_SCHEDULED,
            'status' => Call::STATUS_ACCEPTED,
        ]);
    }

    public function adHoc(): static
    {
        return $this->state([
            'call_type' => Call::TYPE_AD_HOC,
            'appointment_id' => null,
            'status' => Call::STATUS_REQUESTED,
            'accepted_at' => null,
        ]);
    }

    public function requested(): static
    {
        return $this->state(['status' => Call::STATUS_REQUESTED]);
    }

    public function ringing(): static
    {
        return $this->state(['status' => Call::STATUS_RINGING]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => Call::STATUS_ACCEPTED,
            'accepted_at' => Carbon::now()->subMinutes(2),
        ]);
    }

    public function ended(): static
    {
        return $this->state([
            'status' => Call::STATUS_ENDED,
            'ended_at' => Carbon::now()->subMinutes(1),
            'call_closed_reason' => Call::CLOSED_REASON_ENDED_BY_USER,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => Call::STATUS_REJECTED]);
    }

    public function forParticipants(Doctor $doctor, Patient $patient): static
    {
        return $this->state([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
        ]);
    }
}
