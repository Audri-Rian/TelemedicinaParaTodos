<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MedicalDocument>
 */
class MedicalDocumentFactory extends Factory
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
            'uploaded_by' => User::factory(),
            'appointment_id' => null,
            'category' => $this->faker->randomElement(['exam', 'prescription', 'report', 'other']),
            'name' => $this->faker->sentence(3),
            'file_path' => 'documents/' . $this->faker->uuid() . '.pdf',
            'file_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(50_000, 600_000),
            'description' => $this->faker->optional()->sentence(),
            'metadata' => ['source' => $this->faker->company()],
            'visibility' => $this->faker->randomElement(['patient', 'doctor', 'shared']),
        ];
    }
}
