<?php

namespace Tests\Unit\Integrations\Mappers;

use App\Integrations\Mappers\DiagnosisFhirMapper;
use App\Models\Diagnosis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DiagnosisFhirMapperTest extends TestCase
{
    use RefreshDatabase;

    private DiagnosisFhirMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new DiagnosisFhirMapper();
        config(['integrations.fhir.system_url' => 'https://telemedicina.example.com/fhir']);
    }

    /**
     * Cria um Diagnosis em memória (não persistido) com IDs fake.
     *
     * Usado nos testes que exercitam o ramo defensivo do mapper quando
     * cid10_code/description podem ser null — o schema tem NOT NULL em
     * cid10_code, então persistir via factory não é possível nesses cenários.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeNonPersistedDiagnosis(array $overrides = []): Diagnosis
    {
        $diagnosis = new Diagnosis(array_merge([
            'appointment_id' => (string) Str::uuid(),
            'doctor_id' => (string) Str::uuid(),
            'patient_id' => (string) Str::uuid(),
            'cid10_code' => null,
            'cid10_description' => null,
            'diagnosis_type' => Diagnosis::TYPE_PRINCIPAL,
        ], $overrides));
        $diagnosis->id = (string) Str::uuid();

        return $diagnosis;
    }

    public function test_maps_complete_diagnosis_to_fhir_condition(): void
    {
        $diagnosis = Diagnosis::factory()->create([
            'cid10_code' => 'J06.9',
            'cid10_description' => 'Infecção aguda das vias aéreas superiores não especificada',
            'description' => 'Paciente com sintomas gripais há 3 dias.',
        ]);

        $fhir = $this->mapper->toFhir($diagnosis);

        $this->assertEquals('Condition', $fhir['resourceType']);
        $this->assertEquals('J06.9', $fhir['code']['coding'][0]['code']);
        $this->assertEquals('http://hl7.org/fhir/sid/icd-10', $fhir['code']['coding'][0]['system']);
        $this->assertEquals(
            'Infecção aguda das vias aéreas superiores não especificada',
            $fhir['code']['coding'][0]['display']
        );

        $this->assertEquals("Patient/{$diagnosis->patient_id}", $fhir['subject']['reference']);
        $this->assertEquals("Encounter/{$diagnosis->appointment_id}", $fhir['encounter']['reference']);
        $this->assertEquals("Practitioner/{$diagnosis->doctor_id}", $fhir['recorder']['reference']);

        $this->assertEquals('encounter-diagnosis', $fhir['category'][0]['coding'][0]['code']);
        $this->assertEquals(
            'http://terminology.hl7.org/CodeSystem/condition-category',
            $fhir['category'][0]['coding'][0]['system']
        );

        $this->assertEquals('Paciente com sintomas gripais há 3 dias.', $fhir['note'][0]['text']);
    }

    public function test_diagnosis_without_cid10_produces_empty_coding(): void
    {
        $diagnosis = $this->makeNonPersistedDiagnosis();

        $fhir = $this->mapper->toFhir($diagnosis);

        $this->assertSame([], $fhir['code']['coding']);
    }

    public function test_diagnosis_without_description_omits_note(): void
    {
        $diagnosis = Diagnosis::factory()->create(['description' => null]);

        $fhir = $this->mapper->toFhir($diagnosis);

        $this->assertArrayNotHasKey('note', $fhir);
    }

    public function test_identifier_uses_configured_fhir_system_url(): void
    {
        config(['integrations.fhir.system_url' => 'https://custom.fhir.example.com']);

        $diagnosis = Diagnosis::factory()->create();

        $fhir = $this->mapper->toFhir($diagnosis);

        $this->assertEquals(
            'https://custom.fhir.example.com/diagnosis-id',
            $fhir['identifier'][0]['system']
        );
        $this->assertEquals($diagnosis->id, $fhir['identifier'][0]['value']);
    }

    public function test_subject_reference_points_to_patient(): void
    {
        $diagnosis = Diagnosis::factory()->create();

        $fhir = $this->mapper->toFhir($diagnosis);

        $this->assertStringStartsWith('Patient/', $fhir['subject']['reference']);
    }

    public function test_recorder_reference_points_to_practitioner(): void
    {
        $diagnosis = Diagnosis::factory()->create();

        $fhir = $this->mapper->toFhir($diagnosis);

        $this->assertStringStartsWith('Practitioner/', $fhir['recorder']['reference']);
    }

    public function test_diagnosis_with_only_cid10_description_still_produces_coding(): void
    {
        $diagnosis = $this->makeNonPersistedDiagnosis([
            'cid10_description' => 'Hipótese diagnóstica sem CID',
        ]);

        $fhir = $this->mapper->toFhir($diagnosis);

        // Tem description → coding não é vazio
        $this->assertNotEmpty($fhir['code']['coding']);
        $this->assertNull($fhir['code']['coding'][0]['code']);
        $this->assertEquals('Hipótese diagnóstica sem CID', $fhir['code']['coding'][0]['display']);
    }
}
