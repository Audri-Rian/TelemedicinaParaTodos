<?php

namespace Tests\Feature;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Policies\ConversationPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingTest extends TestCase
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

    // --- Messaging pages ---

    public function test_doctor_can_access_messages_page(): void
    {
        $response = $this->actingAs($this->doctorUser)->get(route('doctor.messages'));

        $response->assertOk();
    }

    public function test_patient_can_access_messages_page(): void
    {
        $response = $this->actingAs($this->patientUser)->get(route('patient.messages'));

        $response->assertOk();
    }

    public function test_guest_cannot_access_doctor_messages(): void
    {
        $response = $this->get(route('doctor.messages'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_patient_messages(): void
    {
        $response = $this->get(route('patient.messages'));

        $response->assertRedirect(route('login'));
    }

    // --- ConversationPolicy: viewConversation ---

    public function test_doctor_can_view_conversation_with_patient_they_have_appointment_with(): void
    {
        Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->viewConversation($this->doctorUser, $this->patientUser->id));
    }

    public function test_doctor_cannot_view_conversation_without_shared_appointment(): void
    {
        $policy = new ConversationPolicy;

        $this->assertFalse($policy->viewConversation($this->doctorUser, $this->patientUser->id));
    }

    public function test_user_can_always_view_own_conversation(): void
    {
        $policy = new ConversationPolicy;

        $this->assertTrue($policy->viewConversation($this->doctorUser, $this->doctorUser->id));
    }

    // --- ConversationPolicy: sendMessage ---

    public function test_doctor_can_send_message_to_patient_with_shared_appointment(): void
    {
        Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->sendMessage($this->doctorUser, $this->patientUser->id));
    }

    public function test_patient_can_send_message_to_doctor_with_shared_appointment(): void
    {
        Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->sendMessage($this->patientUser, $this->doctorUser->id));
    }

    public function test_cannot_send_message_without_shared_appointment(): void
    {
        $policy = new ConversationPolicy;

        $this->assertFalse($policy->sendMessage($this->doctorUser, $this->patientUser->id));
    }

    public function test_user_cannot_send_message_to_themselves(): void
    {
        $policy = new ConversationPolicy;

        $this->assertFalse($policy->sendMessage($this->doctorUser, $this->doctorUser->id));
    }

    // --- ConversationPolicy: sendMessageInAppointment ---

    public function test_doctor_can_message_patient_in_their_appointment(): void
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subMinutes(5),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now()->subMinutes(5),
        ]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->sendMessageInAppointment(
            $this->doctorUser,
            $appointment->id,
            $this->patientUser->id
        ));
    }

    public function test_patient_can_message_doctor_in_their_appointment(): void
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subMinutes(5),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now()->subMinutes(5),
        ]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->sendMessageInAppointment(
            $this->patientUser,
            $appointment->id,
            $this->doctorUser->id
        ));
    }

    public function test_third_party_cannot_message_in_appointment(): void
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subMinutes(5),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now()->subMinutes(5),
        ]);

        $thirdUser = User::factory()->create();
        $policy = new ConversationPolicy;

        $this->assertFalse($policy->sendMessageInAppointment(
            $thirdUser,
            $appointment->id,
            $this->doctorUser->id
        ));
    }
}
