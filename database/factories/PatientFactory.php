<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'date_of_birth' => $this->faker->dateTimeBetween('-80 years', '-18 years'),
            'phone_number' => $this->faker->numerify('119########'),
            'emergency_contact' => $this->faker->name(),
            'emergency_phone' => $this->faker->numerify('119########'),
            'medical_history' => $this->faker->optional()->paragraph(),
            'allergies' => $this->faker->optional()->sentence(),
            'current_medications' => $this->faker->optional()->sentence(),
            'blood_type' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'height' => $this->faker->numberBetween(150, 200),
            'weight' => $this->faker->numberBetween(50, 120),
            'insurance_provider' => $this->faker->optional()->company(),
            'insurance_number' => $this->faker->optional()->numerify('##########'),
            'status' => 'active',
            'consent_telemedicine' => $this->faker->boolean(80),
        ];
    }
}
