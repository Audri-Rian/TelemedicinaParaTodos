<?php

namespace Database\Factories;

use App\MedicalRecord\Infrastructure\Persistence\Models\Prescription;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\MedicalRecord\Infrastructure\Persistence\Models\Prescription>
 */
class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

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
            'medications' => [[
                'name' => $this->faker->word(),
                'dosage' => $this->faker->randomElement(['500mg', '1 comprimido']),
                'frequency' => $this->faker->randomElement(['8/8h', '12/12h']),
                'duration' => $this->faker->randomElement(['5 dias', '7 dias']),
            ]],
            'instructions' => $this->faker->sentence(),
            'valid_until' => $this->faker->dateTimeBetween('+5 days', '+2 months'),
            'status' => 'active',
            'metadata' => ['notes' => $this->faker->sentence()],
            'issued_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
