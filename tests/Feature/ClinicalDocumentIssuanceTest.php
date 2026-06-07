<?php

namespace Tests\Feature;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ClinicalDocumentIssuanceTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private Patient $patient;

    private Appointments $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->doctor = Doctor::factory()->create();
        $this->patient = Patient::factory()->create();
        $this->appointment = $this->createAppointment(Appointments::STATUS_IN_PROGRESS, now()->subMinutes(10));
    }

    private function createAppointment(string $status, $scheduledAt): Appointments
    {
        return Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => $scheduledAt,
            'access_code' => 'DOC'.fake()->unique()->numerify('###'),
            'status' => $status,
        ]);
    }

    public function test_doctor_can_issue_prescription(): void
    {
        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.prescriptions.store', $this->patient), [
                'appointment_id' => $this->appointment->id,
                'medications' => [
                    ['name' => 'Losartana potássica 50 mg', 'dosage' => '1 comprimido', 'frequency' => 'A cada 24 horas'],
                ],
                'instructions' => 'Uso contínuo.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Prescrição emitida com sucesso.');
        $this->assertDatabaseHas('prescriptions', [
            'appointment_id' => $this->appointment->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
        ]);
    }

    public function test_doctor_can_request_examination(): void
    {
        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.examinations.store', $this->patient), [
                'appointment_id' => $this->appointment->id,
                'name' => 'Hemograma completo',
                'type' => 'lab',
                'justification' => 'Avaliação de rotina.',
                'priority' => 'normal',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Exame solicitado com sucesso.');
        $this->assertDatabaseHas('examinations', [
            'appointment_id' => $this->appointment->id,
            'name' => 'Hemograma completo',
            'patient_id' => $this->patient->id,
        ]);
    }

    public function test_doctor_can_issue_certificate(): void
    {
        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.certificates.store', $this->patient), [
                'appointment_id' => $this->appointment->id,
                'type' => 'absence',
                'start_date' => now()->format('Y-m-d'),
                'days' => 3,
                'reason' => 'Necessita repouso domiciliar.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Atestado emitido com sucesso.');
        $this->assertDatabaseHas('medical_certificates', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'type' => 'absence',
        ]);
    }

    public function test_doctor_can_issue_for_appointment_scheduled_today(): void
    {
        $scheduledToday = $this->createAppointment(Appointments::STATUS_SCHEDULED, now()->addHours(2));

        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.examinations.store', $this->patient), [
                'appointment_id' => $scheduledToday->id,
                'name' => 'Glicemia de jejum',
                'type' => 'lab',
                'justification' => 'Preparação para a consulta de hoje.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Exame solicitado com sucesso.');
        $this->assertDatabaseHas('examinations', ['appointment_id' => $scheduledToday->id]);
    }

    public function test_doctor_cannot_issue_for_future_scheduled_appointment(): void
    {
        $scheduledTomorrow = $this->createAppointment(Appointments::STATUS_SCHEDULED, now()->addDay()->addHours(2));

        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.examinations.store', $this->patient), [
                'appointment_id' => $scheduledTomorrow->id,
                'name' => 'Glicemia de jejum',
                'type' => 'lab',
                'justification' => 'Tentativa fora da janela elegível.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('examinations', 0);
    }

    public function test_issuance_requires_appointment_id(): void
    {
        $response = $this->actingAs($this->doctor->user)
            ->from(route('doctor.patients.medical-record', $this->patient))
            ->post(route('doctor.patients.medical-record.prescriptions.store', $this->patient), [
                'medications' => [['name' => 'Dipirona']],
            ]);

        $response->assertSessionHasErrors('appointment_id');
        $this->assertDatabaseCount('prescriptions', 0);
    }

    public function test_appointment_must_belong_to_doctor_patient_pair(): void
    {
        $otherDoctor = Doctor::factory()->create();
        $foreignAppointment = Appointments::create([
            'doctor_id' => $otherDoctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => now(),
            'access_code' => 'FOR001',
            'status' => Appointments::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.prescriptions.store', $this->patient), [
                'appointment_id' => $foreignAppointment->id,
                'medications' => [['name' => 'Dipirona 500 mg']],
            ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('prescriptions', 0);
    }

    public function test_doctor_without_relationship_cannot_issue(): void
    {
        $stranger = Doctor::factory()->create();

        $response = $this->actingAs($stranger->user)
            ->post(route('doctor.patients.medical-record.prescriptions.store', $this->patient), [
                'appointment_id' => $this->appointment->id,
                'medications' => [['name' => 'Dipirona 500 mg']],
            ]);

        $response->assertForbidden();
    }

    public function test_eligible_appointments_endpoint_applies_criteria(): void
    {
        $scheduledToday = $this->createAppointment(Appointments::STATUS_SCHEDULED, now()->addHours(2));
        $recentCompleted = $this->createAppointment(Appointments::STATUS_COMPLETED, now()->subDays(10));
        $oldCompleted = $this->createAppointment(Appointments::STATUS_COMPLETED, now()->subDays(45));
        $scheduledTomorrow = $this->createAppointment(Appointments::STATUS_SCHEDULED, now()->addDay()->addHours(2));
        $cancelled = $this->createAppointment(Appointments::STATUS_CANCELLED, now()->subHours(1));

        $response = $this->actingAs($this->doctor->user)
            ->getJson(route('doctor.patients.appointments.eligible-for-documents', $this->patient));

        $response->assertOk();
        $ids = collect($response->json('appointments'))->pluck('id');

        $this->assertTrue($ids->contains($this->appointment->id));
        $this->assertTrue($ids->contains($scheduledToday->id));
        $this->assertTrue($ids->contains($recentCompleted->id));
        $this->assertFalse($ids->contains($oldCompleted->id));
        $this->assertFalse($ids->contains($scheduledTomorrow->id));
        $this->assertFalse($ids->contains($cancelled->id));
    }

    public function test_eligible_appointments_endpoint_blocks_unrelated_doctor(): void
    {
        $stranger = Doctor::factory()->create();

        $this->actingAs($stranger->user)
            ->getJson(route('doctor.patients.appointments.eligible-for-documents', $this->patient))
            ->assertForbidden();
    }
}
