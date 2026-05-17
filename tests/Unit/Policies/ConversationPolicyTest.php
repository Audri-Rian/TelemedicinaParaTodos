<?php

namespace Tests\Unit\Policies;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Policies\ConversationPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConversationPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ConversationPolicy $policy;

    private User $doctorUser;

    private Doctor $doctor;

    private User $patientUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ConversationPolicy;

        $this->doctorUser = User::factory()->create();
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id]);

        $this->patientUser = User::factory()->create();
        $this->patient = Patient::factory()->create(['user_id' => $this->patientUser->id]);
    }

    private function createSharedAppointment(): Appointments
    {
        return Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);
    }

    // --- viewConversation ---

    public function test_users_with_shared_appointment_can_view_conversation(): void
    {
        $this->createSharedAppointment();

        $this->assertTrue($this->policy->viewConversation($this->doctorUser, $this->patientUser->id));
        $this->assertTrue($this->policy->viewConversation($this->patientUser, $this->doctorUser->id));
    }

    public function test_cannot_view_conversation_without_shared_appointment(): void
    {
        $this->assertFalse($this->policy->viewConversation($this->doctorUser, $this->patientUser->id));
    }

    public function test_user_can_view_conversation_with_themselves(): void
    {
        $this->assertTrue($this->policy->viewConversation($this->doctorUser, $this->doctorUser->id));
    }

    public function test_unrelated_users_cannot_view_each_others_conversation(): void
    {
        $strangerUser = User::factory()->create();

        $this->assertFalse($this->policy->viewConversation($this->doctorUser, $strangerUser->id));
    }

    // --- sendMessage ---

    public function test_doctor_can_send_message_with_shared_appointment(): void
    {
        $this->createSharedAppointment();

        $this->assertTrue($this->policy->sendMessage($this->doctorUser, $this->patientUser->id));
    }

    public function test_patient_can_send_message_with_shared_appointment(): void
    {
        $this->createSharedAppointment();

        $this->assertTrue($this->policy->sendMessage($this->patientUser, $this->doctorUser->id));
    }

    public function test_cannot_send_message_without_shared_appointment(): void
    {
        $this->assertFalse($this->policy->sendMessage($this->doctorUser, $this->patientUser->id));
    }

    public function test_user_cannot_send_message_to_themselves(): void
    {
        $this->assertFalse($this->policy->sendMessage($this->doctorUser, $this->doctorUser->id));
    }

    public function test_can_message_across_multiple_appointments(): void
    {
        // Two different appointments between same doctor and patient
        $this->createSharedAppointment();
        Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDays(3),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->assertTrue($this->policy->sendMessage($this->doctorUser, $this->patientUser->id));
    }

    // --- sendMessageInAppointment ---

    public function test_doctor_can_message_patient_in_their_appointment(): void
    {
        $appointment = $this->createSharedAppointment();

        $this->assertTrue($this->policy->sendMessageInAppointment(
            $this->doctorUser,
            $appointment->id,
            $this->patientUser->id
        ));
    }

    public function test_patient_can_message_doctor_in_their_appointment(): void
    {
        $appointment = $this->createSharedAppointment();

        $this->assertTrue($this->policy->sendMessageInAppointment(
            $this->patientUser,
            $appointment->id,
            $this->doctorUser->id
        ));
    }

    public function test_doctor_cannot_message_patient_in_another_doctors_appointment(): void
    {
        $otherDoctorUser = User::factory()->create();
        $otherDoctor = Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);

        $appointment = Appointments::create([
            'doctor_id' => $otherDoctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->assertFalse($this->policy->sendMessageInAppointment(
            $this->doctorUser,
            $appointment->id,
            $this->patientUser->id
        ));
    }

    public function test_third_party_cannot_send_message_in_appointment(): void
    {
        $appointment = $this->createSharedAppointment();
        $stranger = User::factory()->create();

        $this->assertFalse($this->policy->sendMessageInAppointment(
            $stranger,
            $appointment->id,
            $this->doctorUser->id
        ));
    }

    public function test_returns_false_for_non_existent_appointment(): void
    {
        $nonExistentUuid = Str::uuid()->toString();

        $this->assertFalse($this->policy->sendMessageInAppointment(
            $this->doctorUser,
            $nonExistentUuid,
            $this->patientUser->id
        ));
    }
}
