<?php

namespace Tests\Unit\Policies;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentPolicy $policy;

    private User $doctorUser;

    private Doctor $doctor;

    private User $patientUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AppointmentPolicy;

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

    // --- viewAny ---

    public function test_doctor_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->doctorUser));
    }

    public function test_patient_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->patientUser));
    }

    public function test_plain_user_cannot_view_any(): void
    {
        $plainUser = User::factory()->create();

        $this->assertFalse($this->policy->viewAny($plainUser));
    }

    // --- view ---

    public function test_doctor_can_view_own_appointment(): void
    {
        $appointment = $this->makeAppointment();

        $this->assertTrue($this->policy->view($this->doctorUser, $appointment));
    }

    public function test_patient_can_view_own_appointment(): void
    {
        $appointment = $this->makeAppointment();

        $this->assertTrue($this->policy->view($this->patientUser, $appointment));
    }

    public function test_other_doctor_cannot_view_appointment(): void
    {
        $otherDoctorUser = User::factory()->create();
        Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);

        $appointment = $this->makeAppointment();

        $this->assertFalse($this->policy->view($otherDoctorUser, $appointment));
    }

    public function test_other_patient_cannot_view_appointment(): void
    {
        $otherPatientUser = User::factory()->create();
        Patient::factory()->create(['user_id' => $otherPatientUser->id]);

        $appointment = $this->makeAppointment();

        $this->assertFalse($this->policy->view($otherPatientUser, $appointment));
    }

    // --- create ---

    public function test_patient_can_create_appointment(): void
    {
        $this->assertTrue($this->policy->create($this->patientUser));
    }

    public function test_doctor_cannot_create_appointment(): void
    {
        $this->assertFalse($this->policy->create($this->doctorUser));
    }

    // --- update ---

    public function test_doctor_can_update_scheduled_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_SCHEDULED]);

        $this->assertTrue($this->policy->update($this->doctorUser, $appointment));
    }

    public function test_cannot_update_in_progress_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $this->assertFalse($this->policy->update($this->doctorUser, $appointment));
        $this->assertFalse($this->policy->update($this->patientUser, $appointment));
    }

    // --- delete ---

    public function test_doctor_can_delete_cancelled_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_CANCELLED]);

        $this->assertTrue($this->policy->delete($this->doctorUser, $appointment));
    }

    public function test_cannot_delete_in_progress_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_IN_PROGRESS]);

        $this->assertFalse($this->policy->delete($this->doctorUser, $appointment));
    }

    public function test_cannot_delete_completed_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_COMPLETED]);

        $this->assertFalse($this->policy->delete($this->doctorUser, $appointment));
    }

    // --- start ---

    public function test_doctor_can_start_scheduled_appointment_within_window(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subMinutes(5),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->assertTrue($this->policy->start($this->doctorUser, $appointment));
    }

    public function test_cannot_start_appointment_too_early(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->addHours(2),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->assertFalse($this->policy->start($this->doctorUser, $appointment));
    }

    public function test_cannot_start_completed_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subHour(),
            'status' => Appointments::STATUS_COMPLETED,
        ]);

        $this->assertFalse($this->policy->start($this->doctorUser, $appointment));
    }

    // --- end ---

    public function test_doctor_can_end_in_progress_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subMinutes(10),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->assertTrue($this->policy->end($this->doctorUser, $appointment));
    }

    public function test_cannot_end_scheduled_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_SCHEDULED]);

        $this->assertFalse($this->policy->end($this->doctorUser, $appointment));
    }

    // --- cancel ---

    public function test_patient_can_cancel_appointment_with_enough_notice(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->addHours(5),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->assertTrue($this->policy->cancel($this->patientUser, $appointment));
    }

    public function test_cannot_cancel_appointment_too_close(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->addHour(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->assertFalse($this->policy->cancel($this->patientUser, $appointment));
    }

    public function test_cannot_cancel_completed_appointment(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->subHour(),
            'status' => Appointments::STATUS_COMPLETED,
        ]);

        $this->assertFalse($this->policy->cancel($this->patientUser, $appointment));
    }

    // --- reschedule ---

    public function test_doctor_can_reschedule_with_enough_notice(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->addHours(5),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->assertTrue($this->policy->reschedule($this->doctorUser, $appointment));
    }

    public function test_cannot_reschedule_too_close(): void
    {
        $appointment = $this->makeAppointment([
            'scheduled_at' => Carbon::now()->addHour(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->assertFalse($this->policy->reschedule($this->doctorUser, $appointment));
    }

    // --- clinical actions ---

    public function test_doctor_can_create_prescription_for_in_progress_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_IN_PROGRESS]);

        $this->assertTrue($this->policy->createPrescription($this->doctorUser, $appointment));
    }

    public function test_doctor_can_create_prescription_for_completed_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_COMPLETED]);

        $this->assertTrue($this->policy->createPrescription($this->doctorUser, $appointment));
    }

    public function test_doctor_cannot_create_prescription_for_scheduled_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_SCHEDULED]);

        $this->assertFalse($this->policy->createPrescription($this->doctorUser, $appointment));
    }

    public function test_patient_cannot_create_prescription(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_IN_PROGRESS]);

        $this->assertFalse($this->policy->createPrescription($this->patientUser, $appointment));
    }

    public function test_other_doctor_cannot_create_prescription(): void
    {
        $otherDoctorUser = User::factory()->create();
        Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);

        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_IN_PROGRESS]);

        $this->assertFalse($this->policy->createPrescription($otherDoctorUser, $appointment));
    }

    public function test_doctor_can_register_diagnosis_for_in_progress_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => Appointments::STATUS_IN_PROGRESS]);

        $this->assertTrue($this->policy->registerDiagnosis($this->doctorUser, $appointment));
    }

    public function test_doctor_can_complement_only_completed_appointment(): void
    {
        $completedAppointment = $this->makeAppointment(['status' => Appointments::STATUS_COMPLETED]);
        $inProgressAppointment = $this->makeAppointment(['status' => Appointments::STATUS_IN_PROGRESS]);

        $this->assertTrue($this->policy->complement($this->doctorUser, $completedAppointment));
        $this->assertFalse($this->policy->complement($this->doctorUser, $inProgressAppointment));
    }

    public function test_doctor_can_save_draft_for_in_progress_or_completed(): void
    {
        $inProgress = $this->makeAppointment(['status' => Appointments::STATUS_IN_PROGRESS]);
        $completed = $this->makeAppointment(['status' => Appointments::STATUS_COMPLETED]);
        $scheduled = $this->makeAppointment(['status' => Appointments::STATUS_SCHEDULED]);

        $this->assertTrue($this->policy->saveDraft($this->doctorUser, $inProgress));
        $this->assertTrue($this->policy->saveDraft($this->doctorUser, $completed));
        $this->assertFalse($this->policy->saveDraft($this->doctorUser, $scheduled));
    }
}
