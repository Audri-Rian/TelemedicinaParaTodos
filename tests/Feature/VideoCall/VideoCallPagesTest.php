<?php

namespace Tests\Feature\VideoCall;

use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoCallPagesTest extends TestCase
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

    // -------------------------------------------------------------------------
    // Página do médico: GET /doctor/video-call
    // -------------------------------------------------------------------------

    public function test_doctor_can_access_video_call_page(): void
    {
        $response = $this->actingAs($this->doctorUser)
            ->get(route('doctor.video-call'));

        $response->assertOk();
    }

    public function test_guest_cannot_access_doctor_video_call_page(): void
    {
        $response = $this->get(route('doctor.video-call'));

        $response->assertRedirect(route('login'));
    }

    public function test_patient_cannot_access_doctor_video_call_page(): void
    {
        $response = $this->actingAs($this->patientUser)
            ->get(route('doctor.video-call'));

        $response->assertForbidden();
    }

    public function test_doctor_page_returns_appointments_within_window(): void
    {
        Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->actingAs($this->doctorUser)
            ->get(route('doctor.video-call'))
            ->assertInertia(fn ($page) => $page
                ->component('Doctor/VideoCall')
                ->has('appointments', 1)
                ->where('appointments.0.can_start_call', true)
            );
    }

    public function test_doctor_page_does_not_include_far_future_appointments(): void
    {
        // scheduled_at = 2 horas no futuro → fora da janela (lead = 10 min)
        Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now()->addHours(2),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->actingAs($this->doctorUser)
            ->get(route('doctor.video-call'))
            ->assertInertia(fn ($page) => $page
                ->component('Doctor/VideoCall')
                ->where('appointments.0.can_start_call', false)
            );
    }

    public function test_doctor_page_shows_active_call_status_per_appointment(): void
    {
        $appointment = Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $call = Call::factory()->scheduled()->forParticipants($this->doctor, $this->patient)->create([
            'appointment_id' => $appointment->id,
            'status' => Call::STATUS_ACCEPTED,
        ]);
        Room::factory()->create(['call_id' => $call->id]);

        $this->actingAs($this->doctorUser)
            ->get(route('doctor.video-call'))
            ->assertInertia(fn ($page) => $page
                ->component('Doctor/VideoCall')
                ->has('appointments', 1)
                ->where('appointments.0.active_call.status', Call::STATUS_ACCEPTED)
            );
    }

    // -------------------------------------------------------------------------
    // Página do paciente: GET /patient/video-call
    // -------------------------------------------------------------------------

    public function test_patient_can_access_video_call_page(): void
    {
        $response = $this->actingAs($this->patientUser)
            ->get(route('patient.video-call'));

        $response->assertOk();
    }

    public function test_guest_cannot_access_patient_video_call_page(): void
    {
        $response = $this->get(route('patient.video-call'));

        $response->assertRedirect(route('login'));
    }

    public function test_doctor_cannot_access_patient_video_call_page(): void
    {
        $response = $this->actingAs($this->doctorUser)
            ->get(route('patient.video-call'));

        $response->assertForbidden();
    }

    public function test_patient_page_returns_doctors_with_appointments(): void
    {
        Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->actingAs($this->patientUser)
            ->get(route('patient.video-call'))
            ->assertInertia(fn ($page) => $page
                ->component('Patient/VideoCall')
                ->has('users', 1)
                ->where('users.0.id', $this->doctorUser->id)
            );
    }

    public function test_patient_page_marks_can_start_call_when_appointment_in_window(): void
    {
        Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->actingAs($this->patientUser)
            ->get(route('patient.video-call'))
            ->assertInertia(fn ($page) => $page
                ->component('Patient/VideoCall')
                ->where('users.0.canStartCall', true)
            );
    }

    public function test_patient_page_shows_has_recent_consultation_flag(): void
    {
        Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now()->subDays(2),
            'status' => Appointments::STATUS_COMPLETED,
            'ended_at' => Carbon::now()->subDays(2)->addHour(),
        ]);

        $this->actingAs($this->patientUser)
            ->get(route('patient.video-call'))
            ->assertInertia(fn ($page) => $page
                ->component('Patient/VideoCall')
                ->where('users.0.hasRecentConsultation', true)
            );
    }

    public function test_patient_page_returns_empty_when_no_appointments(): void
    {
        $this->actingAs($this->patientUser)
            ->get(route('patient.video-call'))
            ->assertInertia(fn ($page) => $page
                ->component('Patient/VideoCall')
                ->has('users', 0)
            );
    }

    public function test_patient_page_shows_active_scheduled_call_flag(): void
    {
        Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $call = Call::factory()->scheduled()->forParticipants($this->doctor, $this->patient)->create([
            'status' => Call::STATUS_ACCEPTED,
        ]);
        Room::factory()->create(['call_id' => $call->id]);

        $this->actingAs($this->patientUser)
            ->get(route('patient.video-call'))
            ->assertInertia(fn ($page) => $page
                ->component('Patient/VideoCall')
                ->where('users.0.hasActiveScheduledCall', true)
            );
    }
}
