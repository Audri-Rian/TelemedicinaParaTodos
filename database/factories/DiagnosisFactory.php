<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Diagnosis>
 */
class DiagnosisFactory extends Factory
{
    protected $model = Diagnosis::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cid10 = $this->faker->randomElement([
            ['code' => 'J06.9', 'description' => 'Infecção aguda das vias aéreas superiores não especificada'],
            ['code' => 'I10', 'description' => 'Hipertensão essencial (primária)'],
            ['code' => 'E11.9', 'description' => 'Diabetes mellitus não-insulino-dependente sem complicações'],
            ['code' => 'M54.5', 'description' => 'Dor lombar baixa'],
            ['code' => 'K21.9', 'description' => 'Doença de refluxo gastroesofágico sem esofagite'],
        ]);

        // diagnoses.appointment_id é NOT NULL com FK. Enquanto não existe
        // AppointmentsFactory, geramos o registro via DB direto. Quando a
        // AppointmentsFactory for criada (pendência #184 do roadmap), trocar
        // para \App\Models\Appointments::factory().
        return [
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'appointment_id' => function (array $attrs) {
                return $this->ensureAppointment(
                    is_string($attrs['doctor_id'] ?? null) ? $attrs['doctor_id'] : null,
                    is_string($attrs['patient_id'] ?? null) ? $attrs['patient_id'] : null,
                );
            },
            'cid10_code' => $cid10['code'],
            'cid10_description' => $cid10['description'],
            'diagnosis_type' => Diagnosis::TYPE_PRINCIPAL,
            'description' => $this->faker->sentence(),
        ];
    }

    /**
     * Cria um appointment mínimo coerente com o doctor/patient do diagnóstico.
     */
    private function ensureAppointment(?string $doctorId, ?string $patientId): string
    {
        $doctorId = $doctorId ?? Doctor::factory()->create()->id;
        $patientId = $patientId ?? Patient::factory()->create()->id;

        $appointmentId = (string) Str::uuid();
        DB::table('appointments')->insert([
            'id' => $appointmentId,
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'scheduled_at' => now()->subDay(),
            'access_code' => Str::upper(Str::random(8)),
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $appointmentId;
    }

    public function principal(): static
    {
        return $this->state(fn () => ['diagnosis_type' => Diagnosis::TYPE_PRINCIPAL]);
    }

    public function secondary(): static
    {
        return $this->state(fn () => ['diagnosis_type' => Diagnosis::TYPE_SECONDARY]);
    }
}
