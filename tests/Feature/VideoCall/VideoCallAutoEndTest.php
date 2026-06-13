<?php

namespace Tests\Feature\VideoCall;

use App\Contracts\MediaGatewayInterface;
use App\Events\VideoCallEnded;
use App\Jobs\EndEmptyVideoCalls;
use App\Jobs\EndScheduledVideoCalls;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Room;
use App\Services\CallManagerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class VideoCallAutoEndTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = Doctor::factory()->create();
        $this->patient = Patient::factory()->create();

        $this->mock(MediaGatewayInterface::class, function ($mock) {
            $mock->shouldReceive('destroyRoom')->andReturn(null);
        });
    }

    private function scheduledCall(array $overrides = []): Call
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subMinutes(30),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $call = Call::factory()->scheduled()->accepted()->forParticipants($this->doctor, $this->patient)->create(array_merge([
            'appointment_id' => $appointment->id,
        ], $overrides));

        Room::factory()->create(['call_id' => $call->id]);

        return $call;
    }

    public function test_scheduled_window_expiry_ends_call_and_broadcasts(): void
    {
        Event::fake([VideoCallEnded::class]);

        // Janela expirada (scheduled_at + trailing < now) e médico não entrou.
        $call = $this->scheduledCall(['patient_joined_at' => Carbon::now()->subMinutes(20)]);

        (new EndScheduledVideoCalls)->handle(app(CallManagerService::class));

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => Call::STATUS_ENDED,
        ]);

        Event::assertDispatched(VideoCallEnded::class, fn ($e) => $e->call->id === $call->id);
    }

    public function test_empty_room_is_ended_after_inactivity(): void
    {
        Event::fake([VideoCallEnded::class]);

        $stale = Carbon::now()->subMinutes(20);
        $call = $this->scheduledCall([
            'doctor_joined_at' => Carbon::now()->subMinutes(40),
            'patient_joined_at' => Carbon::now()->subMinutes(40),
            'doctor_last_seen_at' => $stale,
            'patient_last_seen_at' => $stale,
        ]);

        (new EndEmptyVideoCalls)->handle(app(CallManagerService::class));

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => Call::STATUS_ENDED,
            'call_closed_reason' => Call::CLOSED_REASON_ROOM_INACTIVE,
        ]);

        Event::assertDispatched(VideoCallEnded::class);
    }

    public function test_doctor_disconnect_ends_call_when_patient_still_present(): void
    {
        Event::fake([VideoCallEnded::class]);

        $call = $this->scheduledCall([
            'doctor_joined_at' => Carbon::now()->subMinutes(20),
            'patient_joined_at' => Carbon::now()->subMinutes(20),
            // Médico caiu há mais que a tolerância (2min); paciente segue presente.
            'doctor_last_seen_at' => Carbon::now()->subMinutes(5),
            'patient_last_seen_at' => Carbon::now()->subSeconds(10),
        ]);

        (new EndEmptyVideoCalls)->handle(app(CallManagerService::class));

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => Call::STATUS_ENDED,
            'call_closed_reason' => Call::CLOSED_REASON_DOCTOR_DISCONNECTED,
        ]);

        Event::assertDispatched(VideoCallEnded::class);
    }

    public function test_active_room_with_recent_presence_is_not_ended(): void
    {
        Event::fake([VideoCallEnded::class]);

        $call = $this->scheduledCall([
            'doctor_joined_at' => Carbon::now()->subMinutes(5),
            'patient_joined_at' => Carbon::now()->subMinutes(5),
            'doctor_last_seen_at' => Carbon::now()->subSeconds(10),
            'patient_last_seen_at' => Carbon::now()->subSeconds(10),
        ]);

        (new EndEmptyVideoCalls)->handle(app(CallManagerService::class));

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => Call::STATUS_ACCEPTED,
        ]);

        Event::assertNotDispatched(VideoCallEnded::class);
    }
}
