<?php

namespace Tests\Unit;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Policies\VideoCallPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoCallPolicyTest extends TestCase
{
    use RefreshDatabase;

    private VideoCallPolicy $policy;

    private User $doctorUser;

    private Doctor $doctor;

    private User $patientUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new VideoCallPolicy;

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

    // --- request ---

    public function test_doctor_can_request_video_call_for_scheduled_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_SCHEDULED]);

        $this->assertTrue($this->policy->request($this->doctorUser, $appointment));
    }

    public function test_patient_can_request_video_call_for_scheduled_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_SCHEDULED]);

        $this->assertTrue($this->policy->request($this->patientUser, $appointment));
    }

    public function test_doctor_can_request_video_call_for_rescheduled_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_RESCHEDULED]);

        $this->assertTrue($this->policy->request($this->doctorUser, $appointment));
    }

    public function test_doctor_can_request_video_call_for_in_progress_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now()->subMinutes(5),
        ]);

        $this->assertTrue($this->policy->request($this->doctorUser, $appointment));
    }

    public function test_cannot_request_video_call_for_completed_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_COMPLETED]);

        $this->assertFalse($this->policy->request($this->doctorUser, $appointment));
    }

    public function test_cannot_request_video_call_for_cancelled_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_CANCELLED]);

        $this->assertFalse($this->policy->request($this->doctorUser, $appointment));
    }

    public function test_third_party_cannot_request_video_call(): void
    {
        $stranger = User::factory()->create();
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_SCHEDULED]);

        $this->assertFalse($this->policy->request($stranger, $appointment));
    }

    // --- accept ---

    public function test_doctor_can_accept_video_call(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_SCHEDULED]);

        $this->assertTrue($this->policy->accept($this->doctorUser, $appointment));
    }

    public function test_cannot_accept_video_call_for_completed_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_COMPLETED]);

        $this->assertFalse($this->policy->accept($this->doctorUser, $appointment));
    }

    // --- reject ---

    public function test_patient_can_reject_video_call(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_SCHEDULED]);

        $this->assertTrue($this->policy->reject($this->patientUser, $appointment));
    }

    public function test_cannot_reject_video_call_for_cancelled_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_CANCELLED]);

        $this->assertFalse($this->policy->reject($this->patientUser, $appointment));
    }

    // --- end ---

    public function test_doctor_can_end_in_progress_video_call(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_IN_PROGRESS]);

        $this->assertTrue($this->policy->end($this->doctorUser, $appointment));
    }

    public function test_patient_can_end_in_progress_video_call(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_IN_PROGRESS]);

        $this->assertTrue($this->policy->end($this->patientUser, $appointment));
    }

    public function test_cannot_end_completed_appointment_video_call(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_COMPLETED]);

        $this->assertFalse($this->policy->end($this->doctorUser, $appointment));
    }

    public function test_third_party_cannot_end_video_call(): void
    {
        $stranger = User::factory()->create();
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_IN_PROGRESS]);

        $this->assertFalse($this->policy->end($stranger, $appointment));
    }
}
