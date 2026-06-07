<?php

namespace Tests\Feature\Patient;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $patientUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patientUser = User::factory()->create();
        $this->patient = Patient::factory()->create(['user_id' => $this->patientUser->id]);
    }

    public function test_patient_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->patientUser)->get(route('patient.dashboard'));

        $response->assertOk();
    }

    public function test_guest_cannot_access_patient_dashboard(): void
    {
        $response = $this->get(route('patient.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_doctor_cannot_access_patient_dashboard(): void
    {
        $doctorUser = User::factory()->create();
        Doctor::factory()->create(['user_id' => $doctorUser->id]);

        $response = $this->actingAs($doctorUser)->get(route('patient.dashboard'));

        $response->assertForbidden();
    }

    public function test_user_without_profile_cannot_access_patient_dashboard(): void
    {
        $plainUser = User::factory()->create();

        $response = $this->actingAs($plainUser)->get(route('patient.dashboard'));

        $response->assertForbidden();
    }

    public function test_dashboard_shows_upcoming_appointments(): void
    {
        $doctorUser = User::factory()->create();
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

        Appointments::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addHours(3),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($this->patientUser)->get(route('patient.dashboard'));

        $response->assertOk();
    }

    public function test_dashboard_shows_completed_appointments_in_history(): void
    {
        $doctorUser = User::factory()->create();
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

        Appointments::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subDays(3),
            'status' => Appointments::STATUS_COMPLETED,
            'started_at' => Carbon::now()->subDays(3),
            'ended_at' => Carbon::now()->subDays(3)->addMinutes(30),
        ]);

        $response = $this->actingAs($this->patientUser)->get(route('patient.dashboard'));

        $response->assertOk();
    }

    public function test_patient_can_access_search_consultations(): void
    {
        $response = $this->actingAs($this->patientUser)->get(route('patient.search-consultations'));

        $response->assertOk();
    }

    public function test_patient_can_access_history(): void
    {
        $response = $this->actingAs($this->patientUser)->get(route('patient.history-consultations'));

        $response->assertOk();
    }

    public function test_patient_can_access_medical_records(): void
    {
        $response = $this->actingAs($this->patientUser)->get(route('patient.medical-records'));

        $response->assertOk();
    }
}
