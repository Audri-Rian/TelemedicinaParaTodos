<?php

namespace Database\Factories;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointments>
 */
class AppointmentsFactory extends Factory
{
    protected $model = Appointments::class;

    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ];
    }

    public function scheduled(): static
    {
        return $this->state([
            'status' => Appointments::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->addDay(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => Appointments::STATUS_IN_PROGRESS,
            'scheduled_at' => Carbon::now()->subMinutes(10),
            'started_at' => Carbon::now()->subMinutes(10),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => Appointments::STATUS_COMPLETED,
            'scheduled_at' => Carbon::now()->subHour(),
            'started_at' => Carbon::now()->subHour(),
            'ended_at' => Carbon::now()->subMinutes(5),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => Appointments::STATUS_CANCELLED,
            'scheduled_at' => Carbon::now()->addDay(),
        ]);
    }

    public function rescheduled(): static
    {
        return $this->state([
            'status' => Appointments::STATUS_RESCHEDULED,
            'scheduled_at' => Carbon::now()->addDays(2),
        ]);
    }

    public function past(): static
    {
        return $this->state([
            'scheduled_at' => Carbon::now()->subDays(2),
        ]);
    }

    public function forDoctorAndPatient(Doctor $doctor, Patient $patient): static
    {
        return $this->state([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
        ]);
    }
}
