<?php

namespace Tests\Feature\Doctor;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctorUser = User::factory()->create();
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id]);
    }

    public function test_doctor_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->doctorUser)->get(route('doctor.dashboard'));

        $response->assertOk();
    }

    public function test_guest_cannot_access_doctor_dashboard(): void
    {
        $response = $this->get(route('doctor.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_patient_cannot_access_doctor_dashboard(): void
    {
        $patientUser = User::factory()->create();
        Patient::factory()->create(['user_id' => $patientUser->id]);

        $response = $this->actingAs($patientUser)->get(route('doctor.dashboard'));

        $response->assertForbidden();
    }

    public function test_user_without_profile_cannot_access_doctor_dashboard(): void
    {
        $plainUser = User::factory()->create();

        $response = $this->actingAs($plainUser)->get(route('doctor.dashboard'));

        $response->assertForbidden();
    }

    public function test_dashboard_shows_upcoming_appointments(): void
    {
        $patientUser = User::factory()->create();
        $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

        Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'scheduled_at' => Carbon::now()->addHours(2),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($this->doctorUser)->get(route('doctor.dashboard'));

        $response->assertOk();
    }

    public function test_dashboard_shows_weekly_stats(): void
    {
        $patientUser = User::factory()->create();
        $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

        Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'scheduled_at' => Carbon::now()->startOfWeek()->addHours(10),
            'status' => Appointments::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->doctorUser)->get(route('doctor.dashboard'));

        $response->assertOk();
    }
}
