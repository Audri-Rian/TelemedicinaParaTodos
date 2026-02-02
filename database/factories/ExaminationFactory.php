<?php

namespace Database\Factories;

use App\MedicalRecord\Infrastructure\Persistence\Models\Examination;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\MedicalRecord\Infrastructure\Persistence\Models\Examination>
 */
class ExaminationFactory extends Factory
{
    protected $model = Examination::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['requested', 'in_progress', 'completed']);

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'appointment_id' => null,
            'type' => $this->faker->randomElement(['lab', 'image', 'other']),
            'name' => $this->faker->randomElement(['Hemograma', 'Raio-X', 'Ressonância']),
            'requested_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'completed_at' => $status === 'completed' ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'results' => $status === 'completed' ? ['summary' => $this->faker->sentence()] : null,
            'attachment_url' => $status === 'completed' ? $this->faker->url() : null,
            'status' => $status,
            'metadata' => ['lab' => $this->faker->company()],
        ];
    }
}
