<?php

namespace Tests\Feature\VideoCall;

use App\Contracts\MediaGatewayInterface;
use App\DataTransferObjects\MediaRoomData;
use App\Events\VideoCallAccepted;
use App\Events\VideoCallEnded;
use App\Events\VideoCallRejected;
use App\Events\VideoCallRequested;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CallControllerTest extends TestCase
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

        $this->mockMediaGateway();
    }

    private function mockMediaGateway(): void
    {
        $this->mock(MediaGatewayInterface::class, function ($mock) {
            $mock->shouldReceive('createRoom')
                ->andReturn(new MediaRoomData('room-test-123', 'sfu-node-1', 'wss://sfu.test/ws'));
            $mock->shouldReceive('destroyRoom')->andReturn(null);
        });
    }

    private function makeRecentAppointment(array $overrides = []): Appointments
    {
        return Appointments::create(array_merge([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subDays(3),
            'status' => Appointments::STATUS_COMPLETED,
            'ended_at' => Carbon::now()->subDays(3)->addHour(),
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // POST /calls — store (criar chamada ad-hoc)
    // -------------------------------------------------------------------------

    public function test_patient_can_create_adhoc_call_with_recent_appointment(): void
    {
        Event::fake([VideoCallRequested::class]);
        $this->makeRecentAppointment();

        $response = $this->actingAs($this->patientUser)->postJson(route('calls.store'), [
            'call_type' => 'ad_hoc',
            'doctor_id' => $this->doctor->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.call_id', fn ($id) => ! empty($id));

        $this->assertDatabaseHas('calls', [
            'call_type' => Call::TYPE_AD_HOC,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => Call::STATUS_REQUESTED,
        ]);

        Event::assertDispatched(VideoCallRequested::class);
    }

    public function test_store_ignores_mass_assignment_of_protected_call_fields(): void
    {
        Event::fake([VideoCallRequested::class]);
        $this->makeRecentAppointment();

        $wrongPatientId = (string) Patient::factory()->create()->id;

        $response = $this->actingAs($this->patientUser)->postJson(route('calls.store'), [
            'call_type' => 'ad_hoc',
            'doctor_id' => $this->doctor->id,
            'status' => Call::STATUS_ENDED,
            'ended_at' => Carbon::now()->subMinute()->toIso8601String(),
            'patient_id' => $wrongPatientId,
        ]);

        $response->assertCreated();

        $callId = $response->json('data.call_id');

        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'status' => Call::STATUS_REQUESTED,
            'patient_id' => $this->patient->id,
        ]);

        $this->assertDatabaseMissing('calls', [
            'id' => $callId,
            'status' => Call::STATUS_ENDED,
            'patient_id' => $wrongPatientId,
        ]);
    }

    public function test_patient_cannot_create_adhoc_call_without_recent_appointment(): void
    {
        // Sem consulta recente — não passa na policy
        $response = $this->actingAs($this->patientUser)->postJson(route('calls.store'), [
            'call_type' => 'ad_hoc',
            'doctor_id' => $this->doctor->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_doctor_cannot_create_adhoc_call(): void
    {
        $response = $this->actingAs($this->doctorUser)->postJson(route('calls.store'), [
            'call_type' => 'ad_hoc',
            'doctor_id' => $this->doctor->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_call(): void
    {
        $response = $this->postJson(route('calls.store'), [
            'call_type' => 'ad_hoc',
            'doctor_id' => $this->doctor->id,
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->patientUser)->postJson(route('calls.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['call_type', 'doctor_id']);
    }

    public function test_store_rejects_invalid_doctor_id(): void
    {
        $response = $this->actingAs($this->patientUser)->postJson(route('calls.store'), [
            'call_type' => 'ad_hoc',
            'doctor_id' => 'not-a-uuid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['doctor_id']);
    }

    // -------------------------------------------------------------------------
    // POST /calls/{call}/accept — aceitar chamada ad-hoc
    // -------------------------------------------------------------------------

    public function test_doctor_can_accept_adhoc_call(): void
    {
        Event::fake([VideoCallAccepted::class]);

        $call = Call::factory()->adHoc()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($this->doctorUser)
            ->postJson(route('calls.accept', $call));

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'sfu_ws_url']]);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => Call::STATUS_ACCEPTED,
        ]);

        Event::assertDispatched(VideoCallAccepted::class);
    }

    public function test_patient_cannot_accept_call(): void
    {
        $call = Call::factory()->adHoc()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($this->patientUser)
            ->postJson(route('calls.accept', $call));

        $response->assertForbidden();
    }

    public function test_other_doctor_cannot_accept_call(): void
    {
        $otherDoctorUser = User::factory()->create();
        Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);

        $call = Call::factory()->adHoc()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($otherDoctorUser)
            ->postJson(route('calls.accept', $call));

        $response->assertForbidden();
    }

    public function test_scheduled_call_cannot_be_manually_accepted(): void
    {
        $call = Call::factory()->scheduled()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($this->doctorUser)
            ->postJson(route('calls.accept', $call));

        $response->assertForbidden();
    }

    public function test_already_ended_call_cannot_be_accepted(): void
    {
        $call = Call::factory()->adHoc()->ended()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($this->doctorUser)
            ->postJson(route('calls.accept', $call));

        $response->assertUnprocessable();
    }

    // -------------------------------------------------------------------------
    // POST /calls/{call}/reject — recusar chamada ad-hoc
    // -------------------------------------------------------------------------

    public function test_doctor_can_reject_adhoc_call(): void
    {
        Event::fake([VideoCallRejected::class]);

        $call = Call::factory()->adHoc()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($this->doctorUser)
            ->postJson(route('calls.reject', $call));

        $response->assertNoContent();

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => Call::STATUS_REJECTED,
        ]);

        Event::assertDispatched(VideoCallRejected::class);
    }

    public function test_patient_cannot_reject_call(): void
    {
        $call = Call::factory()->adHoc()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($this->patientUser)
            ->postJson(route('calls.reject', $call));

        $response->assertForbidden();
    }

    public function test_scheduled_call_cannot_be_manually_rejected(): void
    {
        $call = Call::factory()->scheduled()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($this->doctorUser)
            ->postJson(route('calls.reject', $call));

        $response->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // POST /calls/{call}/end — encerrar chamada
    // -------------------------------------------------------------------------

    public function test_doctor_can_end_active_call(): void
    {
        Event::fake([VideoCallEnded::class]);

        $call = Call::factory()->accepted()->forParticipants($this->doctor, $this->patient)->create();
        Room::factory()->create(['call_id' => $call->id]);

        $response = $this->actingAs($this->doctorUser)
            ->postJson(route('calls.end', $call));

        $response->assertNoContent();

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => Call::STATUS_ENDED,
            'call_closed_reason' => Call::CLOSED_REASON_ENDED_BY_USER,
        ]);

        Event::assertDispatched(VideoCallEnded::class);
    }

    public function test_patient_can_end_active_call(): void
    {
        Event::fake([VideoCallEnded::class]);

        $call = Call::factory()->accepted()->forParticipants($this->doctor, $this->patient)->create();
        Room::factory()->create(['call_id' => $call->id]);

        $response = $this->actingAs($this->patientUser)
            ->postJson(route('calls.end', $call));

        $response->assertNoContent();

        Event::assertDispatched(VideoCallEnded::class);
    }

    public function test_stranger_cannot_end_call(): void
    {
        $strangerUser = User::factory()->create();
        Patient::factory()->create(['user_id' => $strangerUser->id]);

        $call = Call::factory()->accepted()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($strangerUser)
            ->postJson(route('calls.end', $call));

        $response->assertForbidden();
    }

    public function test_cannot_end_already_ended_call(): void
    {
        $call = Call::factory()->ended()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($this->doctorUser)
            ->postJson(route('calls.end', $call));

        $response->assertUnprocessable();
    }

    // -------------------------------------------------------------------------
    // GET /calls/{call} — show
    // -------------------------------------------------------------------------

    public function test_doctor_can_view_own_call(): void
    {
        $call = Call::factory()->accepted()->forParticipants($this->doctor, $this->patient)->create();
        Room::factory()->create(['call_id' => $call->id, 'room_id' => 'room-abc']);

        $response = $this->actingAs($this->doctorUser)
            ->getJson(route('calls.show', $call));

        $response->assertOk()
            ->assertJsonPath('data.call_id', $call->id)
            ->assertJsonPath('data.status', Call::STATUS_ACCEPTED);
    }

    public function test_patient_can_view_own_call(): void
    {
        $call = Call::factory()->accepted()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($this->patientUser)
            ->getJson(route('calls.show', $call));

        $response->assertOk();
    }

    public function test_stranger_cannot_view_call(): void
    {
        $strangerUser = User::factory()->create();
        Patient::factory()->create(['user_id' => $strangerUser->id]);

        $call = Call::factory()->accepted()->forParticipants($this->doctor, $this->patient)->create();

        $response = $this->actingAs($strangerUser)
            ->getJson(route('calls.show', $call));

        $response->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // GET /calls/active — active
    // -------------------------------------------------------------------------

    public function test_doctor_gets_active_call_when_one_exists(): void
    {
        $call = Call::factory()
            ->scheduled()
            ->forParticipants($this->doctor, $this->patient)
            ->create();
        Room::factory()->create(['call_id' => $call->id, 'room_id' => 'room-active-1']);

        $response = $this->actingAs($this->doctorUser)
            ->getJson(route('calls.active'));

        $response->assertOk()
            ->assertJsonPath('data.call_id', $call->id)
            ->assertJsonPath('data.role', 'doctor');
    }

    public function test_patient_gets_active_call_when_one_exists(): void
    {
        $call = Call::factory()
            ->scheduled()
            ->forParticipants($this->doctor, $this->patient)
            ->create();
        Room::factory()->create(['call_id' => $call->id]);

        $response = $this->actingAs($this->patientUser)
            ->getJson(route('calls.active'));

        $response->assertOk()
            ->assertJsonPath('data.role', 'patient');
    }

    public function test_returns_204_when_no_active_call(): void
    {
        $response = $this->actingAs($this->patientUser)
            ->getJson(route('calls.active'));

        $response->assertNoContent();
    }

    public function test_active_call_includes_token_when_accepted_and_room_exists(): void
    {
        $call = Call::factory()
            ->scheduled()
            ->accepted()
            ->forParticipants($this->doctor, $this->patient)
            ->create();
        Room::factory()->create(['call_id' => $call->id]);

        $response = $this->actingAs($this->doctorUser)
            ->getJson(route('calls.active'));

        $response->assertOk();
        $this->assertNotNull($response->json('data.token'));
    }

    public function test_guest_cannot_access_active_call(): void
    {
        $response = $this->getJson(route('calls.active'));

        $response->assertUnauthorized();
    }
}
