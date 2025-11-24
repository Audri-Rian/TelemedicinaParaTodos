<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\VitalSign>
 */
class VitalSignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'appointment_id' => null,
            'recorded_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'blood_pressure_systolic' => $this->faker->numberBetween(100, 140),
            'blood_pressure_diastolic' => $this->faker->numberBetween(60, 90),
            'temperature' => $this->faker->randomFloat(1, 36, 38.5),
            'heart_rate' => $this->faker->numberBetween(60, 100),
            'respiratory_rate' => $this->faker->numberBetween(12, 20),
            'oxygen_saturation' => $this->faker->numberBetween(94, 100),
            'weight' => $this->faker->randomFloat(2, 50, 120),
            'height' => $this->faker->randomFloat(2, 150, 200),
            'notes' => $this->faker->optional()->sentence(),
            'metadata' => ['device' => $this->faker->randomElement(['manual', 'iot'])],
        ];
    }
}
