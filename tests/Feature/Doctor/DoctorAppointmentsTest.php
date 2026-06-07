<?php

namespace Tests\Feature\Doctor;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    private User $patientUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctorUser = User::factory()->create();
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id]);

        $this->patientUser = User::factory()->create();
        $this->patient = Patient::factory()->create(['user_id' => $this->patientUser->id]);
    }

    private function makeAppointment(array $overrides = []): Appointments
    {
        return Appointments::create(array_merge([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ], $overrides));
    }

    // --- Consultas list ---

    public function test_doctor_can_list_consultations(): void
    {
        $response = $this->actingAs($this->doctorUser)->get(route('doctor.consultations'));

        $response->assertOk();
    }

    public function test_guest_cannot_list_doctor_consultations(): void
    {
        $response = $this->get(route('doctor.consultations'));

        $response->assertRedirect(route('login'));
    }

    // --- Consulta detail ---

    public function test_doctor_can_view_own_appointment_detail(): void
    {
        $appointment = $this->makeAppointment();

        $response = $this->actingAs($this->doctorUser)
            ->get(route('doctor.consultations.detail', $appointment));

        $response->assertOk();
    }

    public function test_doctor_cannot_view_other_doctors_appointment(): void
    {
        $otherDoctorUser = User::factory()->create();
        $otherDoctor = Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);

        $appointment = $this->makeAppointment(['doctor_id' => $otherDoctor->id]);

        $response = $this->actingAs($this->doctorUser)
            ->get(route('doctor.consultations.detail', $appointment));

        $response->assertForbidden();
    }

    // --- Start consultation ---

    public function test_doctor_can_start_scheduled_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subMinutes(5),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('doctor.consultations.detail.start', $appointment));

        $response->assertRedirect(route('doctor.consultations.detail', $appointment));
        $this->assertEquals(Appointments::STATUS_IN_PROGRESS, $appointment->fresh()->status);
        $this->assertNotNull($appointment->fresh()->started_at);
    }

    public function test_doctor_cannot_start_appointment_too_early(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->addHours(2),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('doctor.consultations.detail.start', $appointment));

        $response->assertForbidden();
        $this->assertEquals(Appointments::STATUS_SCHEDULED, $appointment->fresh()->status);
    }

    public function test_doctor_cannot_start_completed_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subHour(),
            'status' => Appointments::STATUS_COMPLETED,
            'started_at' => Carbon::now()->subHour(),
            'ended_at' => Carbon::now()->subMinutes(30),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('doctor.consultations.detail.start', $appointment));

        $response->assertForbidden();
    }

    public function test_other_doctor_cannot_start_appointment(): void
    {
        $otherDoctorUser = User::factory()->create();
        Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);

        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subMinutes(5),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($otherDoctorUser)
            ->post(route('doctor.consultations.detail.start', $appointment));

        $response->assertForbidden();
    }

    // --- Save draft ---

    public function test_doctor_can_save_draft_for_in_progress_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subMinutes(10),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('doctor.consultations.detail.save-draft', $appointment), [
                'chief_complaint' => 'Dor de cabeça intensa',
                'diagnosis' => 'Enxaqueca',
                'cid10' => 'G43',
            ]);

        $response->assertRedirect();
        $this->assertEquals('Dor de cabeça intensa', $appointment->fresh()->metadata['chief_complaint']);
    }

    public function test_doctor_cannot_save_draft_for_scheduled_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('doctor.consultations.detail.save-draft', $appointment), [
                'chief_complaint' => 'Teste',
            ]);

        $response->assertForbidden();
    }

    // --- Finalize consultation ---

    public function test_doctor_can_finalize_in_progress_appointment_with_required_fields(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subMinutes(30),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now()->subMinutes(30),
            'metadata' => [
                'chief_complaint' => 'Febre e tosse',
                'diagnosis' => 'Gripe',
            ],
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('doctor.consultations.detail.finalize', $appointment));

        $response->assertRedirect(route('doctor.consultations.detail', $appointment));
        $this->assertEquals(Appointments::STATUS_COMPLETED, $appointment->fresh()->status);
        $this->assertNotNull($appointment->fresh()->ended_at);
    }

    public function test_doctor_cannot_finalize_without_chief_complaint(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subMinutes(30),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now()->subMinutes(30),
            'metadata' => ['diagnosis' => 'Gripe'],
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('doctor.consultations.detail.finalize', $appointment));

        $response->assertSessionHasErrors('chief_complaint');
        $this->assertEquals(Appointments::STATUS_IN_PROGRESS, $appointment->fresh()->status);
    }

    public function test_doctor_cannot_finalize_scheduled_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subMinutes(5),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('doctor.consultations.detail.finalize', $appointment));

        $response->assertForbidden();
    }

    // --- Complement completed appointment ---

    public function test_doctor_can_complement_completed_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subHour(),
            'status' => Appointments::STATUS_COMPLETED,
            'started_at' => Carbon::now()->subHour(),
            'ended_at' => Carbon::now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('doctor.consultations.detail.complement', $appointment), [
                'complementary_notes' => 'Observação adicional sobre a consulta.',
            ]);

        $response->assertRedirect();
        $this->assertEquals('Observação adicional sobre a consulta.', $appointment->fresh()->metadata['complementary_notes']);
    }

    public function test_doctor_cannot_complement_in_progress_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subMinutes(10),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('doctor.consultations.detail.complement', $appointment), [
                'complementary_notes' => 'Teste',
            ]);

        $response->assertForbidden();
    }

    // --- History ---

    public function test_doctor_can_access_history(): void
    {
        $response = $this->actingAs($this->doctorUser)->get(route('doctor.history'));

        $response->assertOk();
    }
}
