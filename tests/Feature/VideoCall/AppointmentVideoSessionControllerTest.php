<?php

namespace Tests\Feature\VideoCall;

use App\Contracts\MediaGatewayInterface;
use App\DataTransferObjects\MediaRoomData;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentVideoSessionControllerTest extends TestCase
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

        config(['services.media_gateway.jwt_secret' => 'test-secret-for-jwt']);

        $this->mock(MediaGatewayInterface::class, function ($mock) {
            $mock->shouldReceive('createRoom')
                ->andReturn(new MediaRoomData('room-test-123', 'sfu-node-1', 'wss://sfu.test/ws'));
            $mock->shouldReceive('destroyRoom')->andReturn(null);
        });
    }

    private function makeAppointmentInWindow(array $overrides = []): Appointments
    {
        // scheduled_at = now, so diff = 0 → dentro da janela (lead=10, trailing=10)
        return Appointments::create(array_merge([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now(),
            'status' => Appointments::STATUS_SCHEDULED,
        ], $overrides));
    }

    private function sessionRoute(Appointments $appointment): string
    {
        return route('appointments.video.session', $appointment);
    }

    // -------------------------------------------------------------------------
    // Acesso à sessão (POST /appointments/{id}/video/session)
    // -------------------------------------------------------------------------

    public function test_doctor_can_join_scheduled_appointment_within_window(): void
    {
        $appointment = $this->makeAppointmentInWindow();

        $response = $this->actingAs($this->doctorUser)
            ->postJson($this->sessionRoute($appointment));

        $response->assertOk()
            ->assertJsonStructure(['data' => ['call_id', 'room_id', 'role', 'token', 'sfu_ws_url', 'window']]);

        $response->assertJsonPath('data.role', 'doctor');
    }

    public function test_patient_can_join_scheduled_appointment_within_window(): void
    {
        $appointment = $this->makeAppointmentInWindow();

        $response = $this->actingAs($this->patientUser)
            ->postJson($this->sessionRoute($appointment));

        $response->assertOk()
            ->assertJsonPath('data.role', 'patient');
    }

    public function test_both_can_join_same_appointment_idempotently(): void
    {
        $appointment = $this->makeAppointmentInWindow();

        $this->actingAs($this->doctorUser)->postJson($this->sessionRoute($appointment))->assertOk();
        $this->actingAs($this->patientUser)->postJson($this->sessionRoute($appointment))->assertOk();

        // Deve haver apenas uma call para o appointment
        $this->assertDatabaseCount('calls', 1);
    }

    public function test_repeated_join_returns_same_call(): void
    {
        $appointment = $this->makeAppointmentInWindow();

        $first = $this->actingAs($this->doctorUser)
            ->postJson($this->sessionRoute($appointment));
        $second = $this->actingAs($this->doctorUser)
            ->postJson($this->sessionRoute($appointment));

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json('data.call_id'), $second->json('data.call_id'));
    }

    public function test_doctor_joined_at_recorded_on_first_join(): void
    {
        $appointment = $this->makeAppointmentInWindow();

        $this->actingAs($this->doctorUser)
            ->postJson($this->sessionRoute($appointment));

        $call = Call::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertNotNull($call->doctor_joined_at);
    }

    public function test_patient_joined_at_recorded_on_first_join(): void
    {
        $appointment = $this->makeAppointmentInWindow();

        $this->actingAs($this->patientUser)
            ->postJson($this->sessionRoute($appointment));

        $call = Call::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertNotNull($call->patient_joined_at);
    }

    public function test_cannot_join_outside_window(): void
    {
        // scheduled_at = 2 horas no futuro → diff = 120 min > lead = 10 min
        $appointment = $this->makeAppointmentInWindow([
            'scheduled_at' => Carbon::now()->addHours(2),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->postJson($this->sessionRoute($appointment));

        $response->assertForbidden();
    }

    public function test_stranger_cannot_join_appointment(): void
    {
        $strangerUser = User::factory()->create();
        Doctor::factory()->create(['user_id' => $strangerUser->id]);

        $appointment = $this->makeAppointmentInWindow();

        $response = $this->actingAs($strangerUser)
            ->postJson($this->sessionRoute($appointment));

        $response->assertForbidden();
    }

    public function test_cannot_join_cancelled_appointment(): void
    {
        $appointment = $this->makeAppointmentInWindow([
            'status' => Appointments::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->postJson($this->sessionRoute($appointment));

        $response->assertForbidden();
    }

    public function test_can_join_in_progress_appointment_within_max_window(): void
    {
        // IN_PROGRESS autorizado até scheduled_at + in_progress_max_minutes (policy.joinSession)
        $appointment = $this->makeAppointmentInWindow([
            'status' => Appointments::STATUS_IN_PROGRESS,
            'scheduled_at' => Carbon::now()->subMinutes(30),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->postJson($this->sessionRoute($appointment));

        $response->assertOk();
    }

    public function test_cannot_join_in_progress_appointment_past_max_window(): void
    {
        $maxMinutes = (int) config('telemedicine.video_call.in_progress_max_minutes', 120);

        $appointment = $this->makeAppointmentInWindow([
            'status' => Appointments::STATUS_IN_PROGRESS,
            'scheduled_at' => Carbon::now()->subMinutes($maxMinutes + 10),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->postJson($this->sessionRoute($appointment));

        $response->assertForbidden();
    }

    public function test_response_includes_window_timestamps(): void
    {
        $appointment = $this->makeAppointmentInWindow();

        $response = $this->actingAs($this->doctorUser)
            ->postJson($this->sessionRoute($appointment));

        $response->assertOk()
            ->assertJsonStructure(['data' => ['window' => ['opens_at', 'closes_at']]]);
    }

    public function test_guest_cannot_join_session(): void
    {
        $appointment = $this->makeAppointmentInWindow();

        $response = $this->postJson($this->sessionRoute($appointment));

        $response->assertUnauthorized();
    }
}
