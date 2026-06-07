<?php

namespace Tests\Feature;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DocumentHubIssuanceTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->doctor = Doctor::factory()->create();
        $this->patient = Patient::factory()->create();
    }

    private function createAppointment(string $status, $scheduledAt, ?Doctor $doctor = null, ?Patient $patient = null): Appointments
    {
        return Appointments::create([
            'doctor_id' => ($doctor ?? $this->doctor)->id,
            'patient_id' => ($patient ?? $this->patient)->id,
            'scheduled_at' => $scheduledAt,
            'access_code' => 'HUB'.fake()->unique()->numerify('#####'),
            'status' => $status,
        ]);
    }

    private function prescriptionPayload(): array
    {
        return [
            'medications' => [
                ['name' => 'Losartana potássica 50 mg', 'dosage' => '1 comprimido', 'frequency' => 'A cada 24 horas'],
            ],
        ];
    }

    public function test_resolution_prefers_in_progress_over_today_and_completed(): void
    {
        $completed = $this->createAppointment(Appointments::STATUS_COMPLETED, now()->subDays(2));
        $today = $this->createAppointment(Appointments::STATUS_SCHEDULED, now()->addHours(3));
        $inProgress = $this->createAppointment(Appointments::STATUS_IN_PROGRESS, now()->subMinutes(15));

        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.prescriptions.store', $this->patient), $this->prescriptionPayload());

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Prescrição emitida com sucesso.');
        $this->assertDatabaseHas('prescriptions', [
            'appointment_id' => $inProgress->id,
            'patient_id' => $this->patient->id,
        ]);
        $this->assertDatabaseMissing('prescriptions', ['appointment_id' => $today->id]);
        $this->assertDatabaseMissing('prescriptions', ['appointment_id' => $completed->id]);
    }

    public function test_resolution_prefers_today_scheduled_over_completed(): void
    {
        $completed = $this->createAppointment(Appointments::STATUS_COMPLETED, now()->subDays(2));
        $today = $this->createAppointment(Appointments::STATUS_SCHEDULED, now()->addHours(3));

        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.prescriptions.store', $this->patient), $this->prescriptionPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('prescriptions', ['appointment_id' => $today->id]);
        $this->assertDatabaseMissing('prescriptions', ['appointment_id' => $completed->id]);
    }

    public function test_resolution_picks_most_recent_completed_within_relationship_window(): void
    {
        $older = $this->createAppointment(Appointments::STATUS_COMPLETED, now()->subDays(8));
        $recent = $this->createAppointment(Appointments::STATUS_COMPLETED, now()->subDays(3));

        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.certificates.store', $this->patient), [
                'type' => 'absence',
                'start_date' => now()->format('Y-m-d'),
                'days' => 2,
                'reason' => 'Necessita repouso domiciliar.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('medical_certificates', ['appointment_id' => $recent->id]);
        $this->assertDatabaseMissing('medical_certificates', ['appointment_id' => $older->id]);
    }

    public function test_resolution_fails_with_422_when_no_eligible_appointment(): void
    {
        $this->createAppointment(Appointments::STATUS_COMPLETED, now()->subDays(15));

        $response = $this->actingAs($this->doctor->user)
            ->postJson(route('doctor.patients.medical-record.prescriptions.store', $this->patient), $this->prescriptionPayload());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appointment_id']);
        $this->assertDatabaseCount('prescriptions', 0);
    }

    public function test_examination_batch_resolves_appointment_automatically(): void
    {
        $inProgress = $this->createAppointment(Appointments::STATUS_IN_PROGRESS, now()->subMinutes(10));

        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.examinations.store-batch', $this->patient), [
                'examinations' => [
                    ['name' => 'Hemograma completo', 'type' => 'lab', 'justification' => 'Avaliação de rotina.'],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('examinations', ['appointment_id' => $inProgress->id]);
    }

    public function test_explicit_appointment_id_still_works(): void
    {
        $completed = $this->createAppointment(Appointments::STATUS_COMPLETED, now()->subDays(20));

        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.prescriptions.store', $this->patient), [
                'appointment_id' => $completed->id,
                ...$this->prescriptionPayload(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('prescriptions', ['appointment_id' => $completed->id]);
    }

    public function test_issuance_blocked_with_403_when_doctor_has_no_active_signature(): void
    {
        $doctor = Doctor::factory()->withoutDigitalSignature()->create();
        $appointment = $this->createAppointment(Appointments::STATUS_IN_PROGRESS, now()->subMinutes(10), $doctor);

        $response = $this->actingAs($doctor->user)
            ->post(route('doctor.patients.medical-record.prescriptions.store', $this->patient), [
                'appointment_id' => $appointment->id,
                ...$this->prescriptionPayload(),
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('prescriptions', 0);
    }

    public function test_issuance_allowed_without_signature_when_flag_disabled(): void
    {
        config(['telemedicine.signature.require_for_issuance' => false]);

        $doctor = Doctor::factory()->withoutDigitalSignature()->create();
        $appointment = $this->createAppointment(Appointments::STATUS_IN_PROGRESS, now()->subMinutes(10), $doctor);

        $response = $this->actingAs($doctor->user)
            ->post(route('doctor.patients.medical-record.prescriptions.store', $this->patient), [
                'appointment_id' => $appointment->id,
                ...$this->prescriptionPayload(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('prescriptions', ['appointment_id' => $appointment->id]);
    }

    public function test_signature_gating_applies_to_certificates_and_examinations(): void
    {
        $doctor = Doctor::factory()->withoutDigitalSignature()->create();
        $appointment = $this->createAppointment(Appointments::STATUS_IN_PROGRESS, now()->subMinutes(10), $doctor);

        $this->actingAs($doctor->user)
            ->post(route('doctor.patients.medical-record.certificates.store', $this->patient), [
                'appointment_id' => $appointment->id,
                'type' => 'absence',
                'start_date' => now()->format('Y-m-d'),
                'days' => 1,
                'reason' => 'Repouso.',
            ])
            ->assertForbidden();

        $this->actingAs($doctor->user)
            ->post(route('doctor.patients.medical-record.examinations.store', $this->patient), [
                'appointment_id' => $appointment->id,
                'name' => 'Hemograma completo',
                'type' => 'lab',
                'justification' => 'Avaliação de rotina.',
            ])
            ->assertForbidden();
    }

    public function test_eligible_patients_endpoint_filters_by_doctor_and_window(): void
    {
        $eligibleByInProgress = $this->patient;
        $this->createAppointment(Appointments::STATUS_IN_PROGRESS, now()->subMinutes(10));

        $eligibleByCompleted = Patient::factory()->create();
        $this->createAppointment(Appointments::STATUS_COMPLETED, now()->subDays(5), null, $eligibleByCompleted);

        $outsideWindow = Patient::factory()->create();
        $this->createAppointment(Appointments::STATUS_COMPLETED, now()->subDays(15), null, $outsideWindow);

        $otherDoctor = Doctor::factory()->create();
        $otherDoctorsPatient = Patient::factory()->create();
        $this->createAppointment(Appointments::STATUS_IN_PROGRESS, now()->subMinutes(5), $otherDoctor, $otherDoctorsPatient);

        $response = $this->actingAs($this->doctor->user)
            ->getJson(route('doctor.patients.eligible-for-documents'));

        $response->assertOk();
        $response->assertJsonPath('relationship_days', 10);

        $ids = collect($response->json('patients'))->pluck('id');
        $this->assertTrue($ids->contains($eligibleByInProgress->id));
        $this->assertTrue($ids->contains($eligibleByCompleted->id));
        $this->assertFalse($ids->contains($outsideWindow->id));
        $this->assertFalse($ids->contains($otherDoctorsPatient->id));
    }

    public function test_eligible_patients_endpoint_forbidden_for_patients(): void
    {
        $response = $this->actingAs($this->patient->user)
            ->getJson(route('doctor.patients.eligible-for-documents'));

        $response->assertForbidden();
    }
}
