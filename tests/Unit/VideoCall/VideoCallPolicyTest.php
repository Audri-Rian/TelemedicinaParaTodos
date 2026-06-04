<?php

namespace Tests\Unit\VideoCall;

use App\Models\Appointments;
use App\Models\Call;
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

        $this->doctorUser->load('doctor');
        $this->patientUser->load('patient');
    }

    private function makeCall(array $overrides = []): Call
    {
        return Call::factory()->forParticipants($this->doctor, $this->patient)->create($overrides);
    }

    // -------------------------------------------------------------------------
    // requestAdhoc
    // -------------------------------------------------------------------------

    public function test_patient_with_recent_appointment_can_request_adhoc(): void
    {
        Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now()->subDays(2),
            'status' => Appointments::STATUS_COMPLETED,
            'ended_at' => Carbon::now()->subDays(2)->addHour(),
        ]);

        $result = $this->policy->requestAdhoc($this->patientUser, $this->doctor);

        $this->assertTrue($result);
    }

    public function test_patient_without_recent_appointment_cannot_request_adhoc(): void
    {
        // Sem consulta nos últimos 7 dias
        $result = $this->policy->requestAdhoc($this->patientUser, $this->doctor);

        $this->assertFalse($result);
    }

    public function test_patient_with_only_old_appointment_cannot_request_adhoc(): void
    {
        Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now()->subDays(10),
            'status' => Appointments::STATUS_COMPLETED,
            'ended_at' => Carbon::now()->subDays(10)->addHour(),
        ]);

        $result = $this->policy->requestAdhoc($this->patientUser, $this->doctor);

        $this->assertFalse($result);
    }

    public function test_doctor_cannot_request_adhoc(): void
    {
        $result = $this->policy->requestAdhoc($this->doctorUser, $this->doctor);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // accept / reject
    // -------------------------------------------------------------------------

    public function test_doctor_of_call_can_accept(): void
    {
        $call = $this->makeCall(['call_type' => Call::TYPE_AD_HOC, 'status' => Call::STATUS_REQUESTED]);

        $result = $this->policy->accept($this->doctorUser, $call);

        $this->assertTrue($result);
    }

    public function test_patient_cannot_accept(): void
    {
        $call = $this->makeCall(['call_type' => Call::TYPE_AD_HOC, 'status' => Call::STATUS_REQUESTED]);

        $result = $this->policy->accept($this->patientUser, $call);

        $this->assertFalse($result);
    }

    public function test_other_doctor_cannot_accept(): void
    {
        $otherDoctorUser = User::factory()->create();
        Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);
        $otherDoctorUser->load('doctor');

        $call = $this->makeCall(['call_type' => Call::TYPE_AD_HOC, 'status' => Call::STATUS_REQUESTED]);

        $result = $this->policy->accept($otherDoctorUser, $call);

        $this->assertFalse($result);
    }

    public function test_scheduled_call_cannot_be_accepted(): void
    {
        $call = $this->makeCall(['call_type' => Call::TYPE_SCHEDULED, 'status' => Call::STATUS_ACCEPTED]);

        $result = $this->policy->accept($this->doctorUser, $call);

        $this->assertFalse($result);
    }

    public function test_doctor_of_call_can_reject(): void
    {
        $call = $this->makeCall(['call_type' => Call::TYPE_AD_HOC, 'status' => Call::STATUS_REQUESTED]);

        $result = $this->policy->reject($this->doctorUser, $call);

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // end
    // -------------------------------------------------------------------------

    public function test_doctor_participant_can_end(): void
    {
        $call = $this->makeCall();

        $result = $this->policy->end($this->doctorUser, $call);

        $this->assertTrue($result);
    }

    public function test_patient_participant_can_end(): void
    {
        $call = $this->makeCall();

        $result = $this->policy->end($this->patientUser, $call);

        $this->assertTrue($result);
    }

    public function test_stranger_cannot_end(): void
    {
        $strangerUser = User::factory()->create();
        Patient::factory()->create(['user_id' => $strangerUser->id]);
        $strangerUser->load('patient');

        $call = $this->makeCall();

        $result = $this->policy->end($strangerUser, $call);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // view
    // -------------------------------------------------------------------------

    public function test_doctor_participant_can_view(): void
    {
        $call = $this->makeCall();

        $this->assertTrue($this->policy->view($this->doctorUser, $call));
    }

    public function test_patient_participant_can_view(): void
    {
        $call = $this->makeCall();

        $this->assertTrue($this->policy->view($this->patientUser, $call));
    }

    public function test_stranger_cannot_view(): void
    {
        $strangerUser = User::factory()->create();
        Patient::factory()->create(['user_id' => $strangerUser->id]);
        $strangerUser->load('patient');

        $call = $this->makeCall();

        $this->assertFalse($this->policy->view($strangerUser, $call));
    }

    // -------------------------------------------------------------------------
    // viewActive
    // -------------------------------------------------------------------------

    public function test_doctor_user_can_view_active(): void
    {
        $this->assertTrue($this->policy->viewActive($this->doctorUser));
    }

    public function test_patient_user_can_view_active(): void
    {
        $this->assertTrue($this->policy->viewActive($this->patientUser));
    }

    public function test_plain_user_cannot_view_active(): void
    {
        $plainUser = User::factory()->make();

        $this->assertFalse($this->policy->viewActive($plainUser));
    }

    // -------------------------------------------------------------------------
    // joinSession
    // -------------------------------------------------------------------------

    public function test_doctor_can_join_session_within_window(): void
    {
        $appointment = Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $result = $this->policy->joinSession($this->doctorUser, $appointment);

        $this->assertTrue($result);
    }

    public function test_patient_can_join_session_within_window(): void
    {
        $appointment = Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $result = $this->policy->joinSession($this->patientUser, $appointment);

        $this->assertTrue($result);
    }

    public function test_cannot_join_session_outside_window(): void
    {
        $appointment = Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now()->addHours(2),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $result = $this->policy->joinSession($this->doctorUser, $appointment);

        $this->assertFalse($result);
    }

    public function test_can_join_in_progress_appointment_any_time(): void
    {
        $appointment = Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now()->subHours(2),
            'status' => Appointments::STATUS_IN_PROGRESS,
        ]);

        $result = $this->policy->joinSession($this->doctorUser, $appointment);

        $this->assertTrue($result);
    }

    public function test_stranger_cannot_join_session(): void
    {
        $strangerUser = User::factory()->create();
        Doctor::factory()->create(['user_id' => $strangerUser->id]);
        $strangerUser->load('doctor');

        $appointment = Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $result = $this->policy->joinSession($strangerUser, $appointment);

        $this->assertFalse($result);
    }

    public function test_cannot_join_cancelled_appointment(): void
    {
        $appointment = Appointments::factory()->forDoctorAndPatient($this->doctor, $this->patient)->create([
            'scheduled_at' => Carbon::now(),
            'status' => Appointments::STATUS_CANCELLED,
        ]);

        $result = $this->policy->joinSession($this->doctorUser, $appointment);

        $this->assertFalse($result);
    }
}
