<?php

namespace Tests\Unit;

use App\Enums\NotificationType;
use App\Jobs\CleanExpiredRedisLocks;
use App\Jobs\EndZombieVideoCalls;
use App\Jobs\MarkNoShowAppointments;
use App\Jobs\SendAppointmentReminders;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\CallManagerService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class MaintenanceJobsTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private User $patientUser;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctorUser = User::factory()->create();
        $this->patientUser = User::factory()->create();
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id]);
        $this->patient = Patient::factory()->create(['user_id' => $this->patientUser->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    /** @test */
    public function mark_no_show_job_marks_overdue_appointments(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-08 15:00:00'));
        config(['telemedicine.maintenance.no_show_grace_minutes' => 15]);

        $overdue = $this->createAppointment([
            'scheduled_at' => now()->subMinutes(20),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);
        $insideGrace = $this->createAppointment([
            'scheduled_at' => now()->subMinutes(10),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        (new MarkNoShowAppointments)->handle(new AppointmentService);

        $this->assertSame(Appointments::STATUS_NO_SHOW, $overdue->fresh()->status);
        $this->assertSame(Appointments::STATUS_SCHEDULED, $insideGrace->fresh()->status);
    }

    /** @test */
    public function zombie_video_job_ends_accepted_calls_and_appointment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-08 15:00:00'));
        config(['telemedicine.video_call.room_max_duration_minutes' => 120]);

        $appointment = $this->createAppointment([
            'scheduled_at' => now()->subHours(3),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => now()->subHours(3),
        ]);
        $call = Call::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => Call::STATUS_ACCEPTED,
            'requested_at' => now()->subHours(3),
            'accepted_at' => now()->subHours(3),
        ]);
        $room = Room::create([
            'call_id' => $call->id,
            'room_id' => 'room-zombie-1',
        ]);
        $callManager = Mockery::mock(CallManagerService::class);
        $callManager->shouldReceive('destroyRoom')->once()->with(Mockery::on(
            fn (Room $receivedRoom) => $receivedRoom->is($room)
        ));

        (new EndZombieVideoCalls)->handle(new AppointmentService, $callManager);

        $this->assertSame(Call::STATUS_ENDED, $call->fresh()->status);
        $this->assertSame(Appointments::STATUS_COMPLETED, $appointment->fresh()->status);
    }

    /** @test */
    public function zombie_video_job_marks_stale_requested_calls_as_missed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-08 15:00:00'));
        config(['telemedicine.video_call.room_inactive_minutes' => 60]);

        $appointment = $this->createAppointment(['scheduled_at' => now()->subHour()]);
        $call = Call::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => Call::STATUS_REQUESTED,
            'requested_at' => now()->subMinutes(90),
        ]);
        $callManager = Mockery::mock(CallManagerService::class);
        $callManager->shouldNotReceive('destroyRoom');

        (new EndZombieVideoCalls)->handle(new AppointmentService, $callManager);

        $this->assertSame(Call::STATUS_MISSED, $call->fresh()->status);
        $this->assertNotNull($call->fresh()->ended_at);
    }

    /** @test */
    public function zombie_video_job_keeps_call_active_when_room_cleanup_fails(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-08 15:00:00'));
        config(['telemedicine.video_call.room_max_duration_minutes' => 120]);

        $appointment = $this->createAppointment([
            'scheduled_at' => now()->subHours(3),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => now()->subHours(3),
        ]);
        $call = Call::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => Call::STATUS_ACCEPTED,
            'requested_at' => now()->subHours(3),
            'accepted_at' => now()->subHours(3),
        ]);
        Room::create([
            'call_id' => $call->id,
            'room_id' => 'room-zombie-fail',
        ]);
        $callManager = Mockery::mock(CallManagerService::class);
        $callManager->shouldReceive('destroyRoom')->once()->andThrow(new \RuntimeException('SFU indisponível'));

        $this->expectException(\RuntimeException::class);

        try {
            (new EndZombieVideoCalls)->handle(new AppointmentService, $callManager);
        } finally {
            $this->assertSame(Call::STATUS_ACCEPTED, $call->fresh()->status);
            $this->assertNull($call->fresh()->ended_at);
            $this->assertSame(Appointments::STATUS_IN_PROGRESS, $appointment->fresh()->status);
        }
    }

    /** @test */
    public function appointment_reminder_job_does_not_resend_same_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-08 09:00:00'));
        config([
            'telemedicine.reminders.send_before_hours' => [24],
            'telemedicine.reminders.max_per_appointment' => 2,
        ]);

        $appointment = $this->createAppointment([
            'scheduled_at' => now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService
            ->shouldReceive('create')
            ->twice()
            ->with(
                NotificationType::APPOINTMENT_REMINDER,
                Mockery::on(fn (array $metadata) => $metadata['appointment_id'] === $appointment->id
                    && $metadata['reminder_hours_before'] === 24),
                Mockery::type(User::class),
                ['email', 'in_app']
            );

        $job = new SendAppointmentReminders;
        $job->handle($notificationService);
        $job->handle($notificationService);

        $this->assertSame([24], $appointment->fresh()->metadata['reminders_sent']);
    }

    /** @test */
    public function clean_expired_redis_locks_deletes_only_expired_orphan_locks(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-08 15:00:00'));
        config([
            'telemedicine.maintenance.lock_key_patterns' => ['telemedicine:lock:*'],
            'telemedicine.maintenance.lock_cleanup_max_age_minutes' => 60,
        ]);

        Redis::shouldReceive('command')
            ->once()
            ->with('scan', ['0', 'MATCH', 'telemedicine:lock:*', 'COUNT', 100])
            ->andReturn(['0', ['telemedicine:lock:expired', 'telemedicine:lock:active']]);
        Redis::shouldReceive('ttl')
            ->with('telemedicine:lock:expired')
            ->andReturn(-1);
        Redis::shouldReceive('get')
            ->with('telemedicine:lock:expired')
            ->andReturn(json_encode(['expires_at' => now()->subMinute()->toIso8601String()]));
        Redis::shouldReceive('del')
            ->once()
            ->with('telemedicine:lock:expired');
        Redis::shouldReceive('ttl')
            ->with('telemedicine:lock:active')
            ->andReturn(120);
        Redis::shouldReceive('get')->never()->with('telemedicine:lock:active');

        (new CleanExpiredRedisLocks)->handle();

        $this->addToAssertionCount(1);
    }

    private function createAppointment(array $attributes = []): Appointments
    {
        return Appointments::create(array_merge([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ], $attributes));
    }
}
