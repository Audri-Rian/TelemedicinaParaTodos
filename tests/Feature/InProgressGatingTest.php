<?php

namespace Tests\Feature;

use App\Jobs\EndStuckInProgressAppointments;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use App\Models\Patient;
use App\Policies\VideoCallPolicy;
use App\Services\AppointmentService;
use App\Services\CallManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InProgressGatingTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        config(['telemedicine.video_call.in_progress_max_minutes' => 120]);

        $this->doctor = Doctor::factory()->create();
        $this->patient = Patient::factory()->create();
    }

    private function createInProgressAppointment(int $minutesAgo): Appointments
    {
        return Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => now()->subMinutes($minutesAgo),
            'access_code' => 'GATE01',
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    private function runJob(): void
    {
        (new EndStuckInProgressAppointments)->handle(
            app(AppointmentService::class),
            app(CallManagerService::class),
        );
    }

    public function test_policy_allows_join_for_in_progress_within_window(): void
    {
        $appointment = $this->createInProgressAppointment(30);

        $this->assertTrue(app(VideoCallPolicy::class)->joinSession($this->doctor->user, $appointment));
        $this->assertTrue(app(VideoCallPolicy::class)->joinSession($this->patient->user, $appointment));
    }

    public function test_policy_blocks_join_for_in_progress_past_window(): void
    {
        $appointment = $this->createInProgressAppointment(200);

        $this->assertFalse(app(VideoCallPolicy::class)->joinSession($this->doctor->user, $appointment));
        $this->assertFalse(app(VideoCallPolicy::class)->joinSession($this->patient->user, $appointment));
    }

    public function test_doctor_page_blocks_expired_in_progress(): void
    {
        $appointment = $this->createInProgressAppointment(200);

        $this->actingAs($this->doctor->user)
            ->get(route('doctor.video-call'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Doctor/VideoCall')
                ->where('appointments.0.id', $appointment->id)
                ->where('appointments.0.can_start_call', false)
                ->where('appointments.0.time_window_message', 'Consulta encerrada — janela expirada'));
    }

    public function test_doctor_page_allows_in_progress_within_window(): void
    {
        $appointment = $this->createInProgressAppointment(30);

        $this->actingAs($this->doctor->user)
            ->get(route('doctor.video-call'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Doctor/VideoCall')
                ->where('appointments.0.id', $appointment->id)
                ->where('appointments.0.can_start_call', true)
                ->where('appointments.0.time_window_message', 'Consulta em andamento'));
    }

    public function test_patient_page_blocks_expired_in_progress(): void
    {
        $this->createInProgressAppointment(200);

        $this->actingAs($this->patient->user)
            ->get(route('patient.video-call'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Patient/VideoCall')
                ->where('users.0.canStartCall', false)
                ->where('users.0.timeWindowMessage', 'Consulta encerrada — janela expirada'));
    }

    public function test_job_completes_stuck_in_progress_without_call(): void
    {
        $appointment = $this->createInProgressAppointment(200);

        $this->runJob();

        $appointment->refresh();
        $this->assertSame(Appointments::STATUS_COMPLETED, $appointment->status);
        $this->assertNotNull($appointment->ended_at);
    }

    public function test_job_ends_orphan_open_call_with_appointment(): void
    {
        $appointment = $this->createInProgressAppointment(200);
        $call = Call::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $appointment->id,
            'status' => Call::STATUS_ACCEPTED,
            'doctor_joined_at' => null,
            'patient_joined_at' => null,
        ]);

        $this->runJob();

        $this->assertSame(Appointments::STATUS_COMPLETED, $appointment->refresh()->status);
        $call->refresh();
        $this->assertSame(Call::STATUS_ENDED, $call->status);
        $this->assertNotNull($call->ended_at);
    }

    public function test_job_skips_appointment_with_active_session(): void
    {
        $appointment = $this->createInProgressAppointment(200);
        $call = Call::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $appointment->id,
            'status' => Call::STATUS_ACCEPTED,
            'doctor_joined_at' => now()->subMinutes(30),
            'patient_joined_at' => now()->subMinutes(29),
        ]);

        $this->runJob();

        $this->assertSame(Appointments::STATUS_IN_PROGRESS, $appointment->refresh()->status);
        $this->assertSame(Call::STATUS_ACCEPTED, $call->refresh()->status);
    }

    public function test_job_ignores_in_progress_within_window(): void
    {
        $appointment = $this->createInProgressAppointment(30);

        $this->runJob();

        $this->assertSame(Appointments::STATUS_IN_PROGRESS, $appointment->refresh()->status);
    }

    public function test_job_is_idempotent(): void
    {
        $appointment = $this->createInProgressAppointment(200);

        $this->runJob();
        $firstEndedAt = $appointment->refresh()->ended_at;

        $this->runJob();

        $appointment->refresh();
        $this->assertSame(Appointments::STATUS_COMPLETED, $appointment->status);
        $this->assertTrue($firstEndedAt->equalTo($appointment->ended_at));
    }
}
