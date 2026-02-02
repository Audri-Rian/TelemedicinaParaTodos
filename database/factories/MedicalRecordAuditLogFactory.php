<?php

namespace Database\Factories;

use App\MedicalRecord\Infrastructure\Persistence\Models\MedicalRecordAuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\MedicalRecord\Infrastructure\Persistence\Models\MedicalRecordAuditLog>
 */
class MedicalRecordAuditLogFactory extends Factory
{
    protected $model = MedicalRecordAuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['view', 'export', 'upload']),
            'resource_type' => $this->faker->randomElement(['appointment', 'document', null]),
            'resource_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'metadata' => ['info' => $this->faker->sentence()],
        ];
    }
}
