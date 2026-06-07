<?php

namespace Tests\Unit\Integrations\Mappers;

use App\Integrations\DTOs\ExamOrderDto;
use App\Integrations\Mappers\ExamOrderFhirMapper;
use Tests\TestCase;

class ExamOrderFhirMapperTest extends TestCase
{
    private ExamOrderFhirMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new ExamOrderFhirMapper();
        config(['integrations.fhir.system_url' => 'https://telemedicina.example.com/fhir']);
    }

    private function makeDto(array $overrides = []): ExamOrderDto
    {
        // array_key_exists preserva null overrides (? ?? não funciona para testes de null)
        $values = array_merge([
            'examinationId' => 'exam-123',
            'patientId' => 'patient-456',
            'doctorId' => 'doctor-789',
            'appointmentId' => 'appt-abc',
            'examName' => 'Hemograma completo',
            'examType' => 'lab',
            'requestedAt' => '2026-04-15T10:00:00+00:00',
            'metadata' => ['priority' => 'routine'],
            'patientCns' => '706000000000001',
            'doctorCns' => '706000000000002',
        ], $overrides);

        return new ExamOrderDto(
            examinationId: $values['examinationId'],
            patientId: $values['patientId'],
            doctorId: $values['doctorId'],
            appointmentId: $values['appointmentId'],
            examName: $values['examName'],
            examType: $values['examType'],
            requestedAt: $values['requestedAt'],
            metadata: $values['metadata'],
            patientCns: $values['patientCns'],
            doctorCns: $values['doctorCns'],
        );
    }

    public function test_maps_complete_order_to_service_request(): void
    {
        $dto = $this->makeDto();

        $fhir = $this->mapper->toFhir($dto);

        $this->assertEquals('ServiceRequest', $fhir['resourceType']);
        $this->assertEquals('active', $fhir['status']);
        $this->assertEquals('order', $fhir['intent']);
        $this->assertEquals('Hemograma completo', $fhir['code']['text']);
        $this->assertEquals('2026-04-15T10:00:00+00:00', $fhir['authoredOn']);
        $this->assertEquals('Patient/patient-456', $fhir['subject']['reference']);
        $this->assertEquals('Practitioner/doctor-789', $fhir['requester']['reference']);
        $this->assertEquals('Encounter/appt-abc', $fhir['encounter']['reference']);
    }

    public function test_lab_exam_type_maps_to_laboratory_category(): void
    {
        $dto = $this->makeDto(['examType' => 'lab']);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertEquals('laboratory', $fhir['category'][0]['coding'][0]['code']);
    }

    public function test_image_exam_type_maps_to_imaging_category(): void
    {
        $dto = $this->makeDto(['examType' => 'image']);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertEquals('imaging', $fhir['category'][0]['coding'][0]['code']);
    }

    public function test_unknown_exam_type_maps_to_exam_category(): void
    {
        $dto = $this->makeDto(['examType' => 'other']);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertEquals('exam', $fhir['category'][0]['coding'][0]['code']);
    }

    public function test_patient_cns_adds_identifier_to_subject_reference(): void
    {
        $dto = $this->makeDto(['patientCns' => '999888777666555']);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertEquals(
            'http://rnds.saude.gov.br/fhir/r4/NamingSystem/cns',
            $fhir['subject']['identifier']['system']
        );
        $this->assertEquals('999888777666555', $fhir['subject']['identifier']['value']);
    }

    public function test_doctor_cns_adds_identifier_to_requester_reference(): void
    {
        $dto = $this->makeDto(['doctorCns' => '111222333444555']);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertEquals(
            'http://rnds.saude.gov.br/fhir/r4/NamingSystem/cns',
            $fhir['requester']['identifier']['system']
        );
        $this->assertEquals('111222333444555', $fhir['requester']['identifier']['value']);
    }

    public function test_null_patient_cns_omits_identifier_in_subject(): void
    {
        $dto = $this->makeDto(['patientCns' => null]);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertArrayNotHasKey('identifier', $fhir['subject']);
    }

    public function test_null_doctor_cns_omits_identifier_in_requester(): void
    {
        $dto = $this->makeDto(['doctorCns' => null]);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertArrayNotHasKey('identifier', $fhir['requester']);
    }

    public function test_null_appointment_id_omits_encounter(): void
    {
        $dto = $this->makeDto(['appointmentId' => null]);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertArrayNotHasKey('encounter', $fhir);
    }

    public function test_null_metadata_omits_note(): void
    {
        $dto = $this->makeDto(['metadata' => null]);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertArrayNotHasKey('note', $fhir);
    }

    public function test_metadata_is_serialized_as_json_note(): void
    {
        $dto = $this->makeDto(['metadata' => ['priority' => 'urgent', 'lab' => 'Pardini']]);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertArrayHasKey('note', $fhir);

        // Validar que é JSON válido ANTES de indexar — evita null index error
        // se o mapper algum dia quebrar o formato da note.
        $decoded = json_decode($fhir['note'][0]['text'], true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'note[0].text não é JSON válido');
        $this->assertIsArray($decoded);

        $this->assertEquals('urgent', $decoded['priority']);
        $this->assertEquals('Pardini', $decoded['lab']);
    }

    public function test_identifier_uses_configured_fhir_system_url(): void
    {
        config(['integrations.fhir.system_url' => 'https://example.org/fhir']);

        $dto = $this->makeDto(['examinationId' => 'exam-abc']);

        $fhir = $this->mapper->toFhir($dto);

        $this->assertEquals('https://example.org/fhir/examination-id', $fhir['identifier'][0]['system']);
        $this->assertEquals('exam-abc', $fhir['identifier'][0]['value']);
    }

    public function test_missing_fhir_system_url_results_in_empty_identifier(): void
    {
        config(['integrations.fhir.system_url' => null]);

        $dto = $this->makeDto();

        $fhir = $this->mapper->toFhir($dto);

        // Mapper usa array_filter(null) que pode remover 'identifier' do resultado.
        // Aceitar ausência OU presença com valor vazio.
        $this->assertEmpty($fhir['identifier'] ?? []);
    }
}
