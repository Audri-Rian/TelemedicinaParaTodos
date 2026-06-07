<?php

namespace Tests\Unit\Integrations\Mappers;

use App\Integrations\Mappers\PrescriptionFhirMapper;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionFhirMapperTest extends TestCase
{
    use RefreshDatabase;

    private PrescriptionFhirMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new PrescriptionFhirMapper();
        config(['integrations.fhir.system_url' => 'https://telemedicina.example.com/fhir']);
    }

    public function test_maps_prescription_with_multiple_medications(): void
    {
        $prescription = Prescription::factory()->create([
            'status' => Prescription::STATUS_ACTIVE,
            'medications' => [
                ['name' => 'Paracetamol 500mg', 'dosage' => '1 comprimido', 'frequency' => '8/8h'],
                ['name' => 'Ibuprofeno 400mg', 'dosage' => '1 comprimido', 'frequency' => '12/12h'],
            ],
            'instructions' => 'Tomar após as refeições',
        ]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertCount(2, $resources);

        foreach ($resources as $r) {
            $this->assertEquals('MedicationRequest', $r['resourceType']);
            $this->assertEquals('active', $r['status']);
            $this->assertEquals('order', $r['intent']);
            $this->assertEquals("Patient/{$prescription->patient_id}", $r['subject']['reference']);
            $this->assertEquals("Practitioner/{$prescription->doctor_id}", $r['requester']['reference']);
        }

        $this->assertEquals("{$prescription->id}-0", $resources[0]['identifier'][0]['value']);
        $this->assertEquals("{$prescription->id}-1", $resources[1]['identifier'][0]['value']);

        $this->assertEquals('Paracetamol 500mg', $resources[0]['medicationCodeableConcept']['text']);
        $this->assertEquals('Ibuprofeno 400mg', $resources[1]['medicationCodeableConcept']['text']);
    }

    public function test_empty_medications_returns_empty_array(): void
    {
        $prescription = Prescription::factory()->create(['medications' => []]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertSame([], $resources);
    }

    public function test_status_mapping(): void
    {
        $mappings = [
            Prescription::STATUS_ACTIVE => 'active',
            Prescription::STATUS_EXPIRED => 'stopped',
            Prescription::STATUS_CANCELLED => 'cancelled',
        ];

        foreach ($mappings as $input => $expected) {
            $prescription = Prescription::factory()->create(['status' => $input]);
            $resources = $this->mapper->toFhir($prescription);
            $this->assertEquals($expected, $resources[0]['status'], "Status '{$input}' should map to '{$expected}'");
        }
    }

    public function test_dosage_instruction_includes_dosage_and_frequency(): void
    {
        $prescription = Prescription::factory()->create([
            'medications' => [
                ['name' => 'Teste', 'dosage' => '500mg', 'frequency' => '6/6h'],
            ],
        ]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertEquals('500mg 6/6h', $resources[0]['dosageInstruction'][0]['text']);
    }

    public function test_medication_without_dosage_and_frequency_omits_dosage_instruction(): void
    {
        $prescription = Prescription::factory()->create([
            'medications' => [['name' => 'Somente nome']],
        ]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertArrayNotHasKey('dosageInstruction', $resources[0]);
    }

    public function test_valid_until_sets_dispense_request_period(): void
    {
        $prescription = Prescription::factory()->create([
            'issued_at' => '2026-04-01 10:00:00',
            'valid_until' => '2026-05-01',
            'medications' => [['name' => 'Teste']],
        ]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertEquals('2026-04-01', $resources[0]['dispenseRequest']['validityPeriod']['start']);
        $this->assertEquals('2026-05-01', $resources[0]['dispenseRequest']['validityPeriod']['end']);
    }

    public function test_null_valid_until_omits_dispense_request(): void
    {
        $prescription = Prescription::factory()->create([
            'valid_until' => null,
            'medications' => [['name' => 'Teste']],
        ]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertArrayNotHasKey('dispenseRequest', $resources[0]);
    }

    public function test_appointment_id_is_included_as_encounter(): void
    {
        // Não existe AppointmentsFactory ainda — inserir via DB direto
        // (a FK é a única constraint relevante para o teste do mapper).
        $prescription = Prescription::factory()->create(['medications' => [['name' => 'Teste']]]);

        $appointmentId = (string) \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('appointments')->insert([
            'id' => $appointmentId,
            'patient_id' => $prescription->patient_id,
            'doctor_id' => $prescription->doctor_id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'access_code' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $prescription->update(['appointment_id' => $appointmentId]);

        $resources = $this->mapper->toFhir($prescription->fresh());

        $this->assertEquals("Encounter/{$appointmentId}", $resources[0]['encounter']['reference']);
    }

    public function test_null_appointment_id_omits_encounter(): void
    {
        $prescription = Prescription::factory()->create([
            'appointment_id' => null,
            'medications' => [['name' => 'Teste']],
        ]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertArrayNotHasKey('encounter', $resources[0]);
    }

    public function test_instructions_are_included_as_note(): void
    {
        $prescription = Prescription::factory()->create([
            'instructions' => 'Evitar dirigir',
            'medications' => [['name' => 'Teste']],
        ]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertEquals('Evitar dirigir', $resources[0]['note'][0]['text']);
    }

    public function test_null_instructions_omits_note(): void
    {
        $prescription = Prescription::factory()->create([
            'instructions' => null,
            'medications' => [['name' => 'Teste']],
        ]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertArrayNotHasKey('note', $resources[0]);
    }

    public function test_authored_on_is_formatted_as_iso8601(): void
    {
        $prescription = Prescription::factory()->create([
            'issued_at' => '2026-04-10 15:30:00',
            'medications' => [['name' => 'Teste']],
        ]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $resources[0]['authoredOn']
        );
    }

    public function test_medication_supports_legacy_medication_key(): void
    {
        $prescription = Prescription::factory()->create([
            'medications' => [['medication' => 'Legado sem "name"']],
        ]);

        $resources = $this->mapper->toFhir($prescription);

        $this->assertEquals('Legado sem "name"', $resources[0]['medicationCodeableConcept']['text']);
    }
}
