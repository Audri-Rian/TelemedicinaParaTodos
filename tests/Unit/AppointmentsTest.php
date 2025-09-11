<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentsTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private User $patientUser;
    private Doctor $doctor;
    private Patient $patient;
    private AppointmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar usuários
        $this->doctorUser = User::factory()->create();
        $this->patientUser = User::factory()->create();
        
        // Criar doctor e patient
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id]);
        $this->patient = Patient::factory()->create(['user_id' => $this->patientUser->id]);

        $this->service = new AppointmentService();
    }

    /** @test */
    public function schema_does_not_have_platform_columns()
    {
        $columns = \Schema::getColumnListing('appointments');
        $this->assertNotContains('conference_platform_id', $columns);
        $this->assertNotContains('platform', $columns);
    }

    /** @test */
    public function it_can_create_an_appointment()
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->assertInstanceOf(Appointments::class, $appointment);
        $this->assertEquals($this->doctor->id, $appointment->doctor_id);
        $this->assertEquals($this->patient->id, $appointment->patient_id);
        $this->assertEquals(Appointments::STATUS_SCHEDULED, $appointment->status);
        $this->assertNotNull($appointment->access_code);
    }

    /** @test */
    public function it_generates_unique_access_code()
    {
        $appointment1 = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
        ]);

        $appointment2 = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDays(2),
        ]);

        $this->assertNotEquals($appointment1->access_code, $appointment2->access_code);
        $this->assertEquals(8, strlen($appointment1->access_code));
        $this->assertEquals(8, strlen($appointment2->access_code));
    }

    /** @test */
    public function it_can_start_an_appointment()
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subMinutes(5), // 5 minutos atrás
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $result = $this->service->start($appointment);

        $this->assertTrue($result);
        $this->assertEquals(Appointments::STATUS_IN_PROGRESS, $appointment->fresh()->status);
        $this->assertNotNull($appointment->fresh()->started_at);
    }

    /** @test */
    public function it_cannot_start_appointment_too_early()
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addHour(), // 1 hora no futuro
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $result = $this->service->start($appointment);

        $this->assertFalse($result);
        $this->assertEquals(Appointments::STATUS_SCHEDULED, $appointment->fresh()->status);
    }

    /** @test */
    public function it_can_end_an_appointment()
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subMinutes(10),
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now()->subMinutes(10),
        ]);

        $result = $this->service->end($appointment);

        $this->assertTrue($result);
        $this->assertEquals(Appointments::STATUS_COMPLETED, $appointment->fresh()->status);
        $this->assertNotNull($appointment->fresh()->ended_at);
    }

    /** @test */
    public function it_can_cancel_an_appointment()
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addHours(3), // 3 horas no futuro
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $result = $this->service->cancel($appointment, 'Paciente não pode comparecer');

        $this->assertTrue($result);
        $this->assertEquals(Appointments::STATUS_CANCELLED, $appointment->fresh()->status);
        $this->assertStringContainsString('Paciente não pode comparecer', $appointment->fresh()->notes);
    }

    /** @test */
    public function it_cannot_cancel_appointment_too_close()
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addHour(), // 1 hora no futuro
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $result = $this->service->cancel($appointment);

        $this->assertFalse($result);
        $this->assertEquals(Appointments::STATUS_SCHEDULED, $appointment->fresh()->status);
    }

    /** @test */
    public function it_can_reschedule_an_appointment()
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $newDateTime = Carbon::now()->addDays(2);
        $result = $this->service->reschedule($appointment, $newDateTime);

        $this->assertTrue($result);
        $this->assertEquals(Appointments::STATUS_RESCHEDULED, $appointment->fresh()->status);
        $this->assertEquals($newDateTime->format('Y-m-d H:i:s'), $appointment->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_mark_as_no_show()
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subHour(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $result = $this->service->markAsNoShow($appointment);

        $this->assertTrue($result);
        $this->assertEquals(Appointments::STATUS_NO_SHOW, $appointment->fresh()->status);
    }

    /** @test */
    public function it_calculates_duration_correctly()
    {
        $startedAt = Carbon::now()->subMinutes(30);
        $endedAt = Carbon::now();

        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subHour(),
            'status' => Appointments::STATUS_COMPLETED,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
        ]);

        $this->assertEquals(30, $appointment->duration);
        $this->assertEquals('30min', $appointment->formatted_duration);
    }

    /** @test */
    public function it_has_correct_relationships()
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
        ]);

        $this->assertInstanceOf(Doctor::class, $appointment->doctor);
        $this->assertInstanceOf(Patient::class, $appointment->patient);
        $this->assertEquals($this->doctor->id, $appointment->doctor->id);
        $this->assertEquals($this->patient->id, $appointment->patient->id);
    }

    /** @test */
    public function it_has_correct_scopes()
    {
        // Criar appointments com diferentes status
        Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDays(2),
            'status' => Appointments::STATUS_CANCELLED,
        ]);

        $this->assertEquals(1, Appointments::scheduled()->count());
        $this->assertEquals(1, Appointments::cancelled()->count());
        $this->assertEquals(2, Appointments::byDoctor($this->doctor->id)->count());
        $this->assertEquals(2, Appointments::byPatient($this->patient->id)->count());
    }

    /** @test */
    public function it_has_correct_accessors()
    {
        $appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        $this->assertTrue($this->service->isUpcoming($appointment));
        $this->assertFalse($this->service->isPast($appointment));
        $this->assertFalse($this->service->isActive($appointment));
    }
}
