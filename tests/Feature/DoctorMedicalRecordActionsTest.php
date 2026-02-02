<?php

namespace Tests\Feature;

use App\MedicalRecord\Infrastructure\Persistence\Models\Diagnosis;
use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorMedicalRecordActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_register_diagnosis(): void
    {
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();

        $appointment = Appointments::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'scheduled_at' => now(),
            'access_code' => 'XYZ123',
            'status' => Appointments::STATUS_IN_PROGRESS,
            'notes' => null,
        ]);

        $response = $this->actingAs($doctor->user)
            ->post(route('doctor.patients.medical-record.diagnoses.store', $patient), [
                'appointment_id' => $appointment->id,
                'cid10_code' => 'J00',
                'cid10_description' => 'Rinite aguda',
                'type' => 'principal',
                'description' => 'Paciente apresentando sintomas compatíveis.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('diagnoses', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'cid10_code' => 'J00',
        ]);
    }
}


