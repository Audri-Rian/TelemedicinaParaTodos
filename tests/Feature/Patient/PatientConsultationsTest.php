<?php

namespace Tests\Feature\Patient;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientConsultationsTest extends TestCase
{
    use RefreshDatabase;

    private User $patientUser;

    private Patient $patient;

    private User $doctorUser;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patientUser = User::factory()->create();
        $this->patient = Patient::factory()->create(['user_id' => $this->patientUser->id]);

        $this->doctorUser = User::factory()->create();
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id]);
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

    // --- Booking page ---

    public function test_patient_can_access_schedule_consultation_with_valid_doctor(): void
    {
        $response = $this->actingAs($this->patientUser)
            ->get(route('patient.schedule-consultation', ['doctor_id' => $this->doctor->id]));

        $response->assertOk();
    }

    public function test_patient_is_redirected_without_doctor_id(): void
    {
        $response = $this->actingAs($this->patientUser)
            ->get(route('patient.schedule-consultation'));

        $response->assertRedirect(route('patient.search-consultations'));
    }

    public function test_patient_is_redirected_for_inactive_doctor(): void
    {
        $inactiveDoctorUser = User::factory()->create();
        $inactiveDoctor = Doctor::factory()->create([
            'user_id' => $inactiveDoctorUser->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->patientUser)
            ->get(route('patient.schedule-consultation', ['doctor_id' => $inactiveDoctor->id]));

        $response->assertRedirect(route('patient.search-consultations'));
    }

    // --- Consultation details ---

    public function test_patient_can_view_own_consultation_details(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subHour(),
            'status' => Appointments::STATUS_COMPLETED,
            'started_at' => Carbon::now()->subHour(),
            'ended_at' => Carbon::now()->subMinutes(30),
        ]);

        $response = $this->actingAs($this->patientUser)
            ->get(route('patient.consultation-details', $appointment));

        $response->assertOk();
    }

    public function test_patient_cannot_view_other_patients_consultation(): void
    {
        $otherPatientUser = User::factory()->create();
        $otherPatient = Patient::factory()->create(['user_id' => $otherPatientUser->id]);

        $appointment = $this->makeAppointment([
            'patient_id' => $otherPatient->id,
        ]);

        $response = $this->actingAs($this->patientUser)
            ->get(route('patient.consultation-details', $appointment));

        $response->assertForbidden();
    }

    public function test_doctor_cannot_access_patient_consultation_detail_route(): void
    {
        $appointment = $this->makeAppointment();

        $response = $this->actingAs($this->doctorUser)
            ->get(route('patient.consultation-details', $appointment));

        $response->assertForbidden();
    }

    // --- Next consultation ---

    public function test_patient_can_access_next_consultation_page(): void
    {
        $response = $this->actingAs($this->patientUser)
            ->get(route('patient.next-consultation'));

        $response->assertOk();
    }

    // --- Video call page ---

    public function test_patient_can_access_video_call_page(): void
    {
        $response = $this->actingAs($this->patientUser)
            ->get(route('patient.video-call'));

        $response->assertOk();
    }
}
