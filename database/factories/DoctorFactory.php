<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
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
            'crm' => $this->faker->unique()->numerify('CRM-######'),
            'biography' => $this->faker->paragraph(),
            // Default apto a emitir documentos (espelha o ambiente demo);
            // use withoutDigitalSignature() para testar o gating de emissão.
            'digital_signature_status' => \App\Models\Doctor::SIGNATURE_ACTIVE,
        ];
    }

    public function withoutDigitalSignature(): static
    {
        return $this->state(fn () => [
            'digital_signature_status' => \App\Models\Doctor::SIGNATURE_NOT_INTEGRATED,
        ]);
    }
}
