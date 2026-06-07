<?php

namespace Tests\Unit\Integrations\Mappers;

use App\Integrations\Mappers\ExamResultFhirMapper;
use Tests\TestCase;

class ExamResultFhirMapperTest extends TestCase
{
    private ExamResultFhirMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new ExamResultFhirMapper();
    }

    public function test_maps_diagnostic_report_with_observations(): void
    {
        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => 'report-001',
            'status' => 'final',
            'basedOn' => [['reference' => 'ServiceRequest/exam-abc']],
            'result' => [
                ['reference' => 'Observation/obs-1'],
                ['reference' => 'Observation/obs-2'],
            ],
            'effectiveDateTime' => '2026-04-15T12:00:00Z',
        ];

        $bundle = [
            [
                'resource' => [
                    'resourceType' => 'Observation',
                    'id' => 'obs-1',
                    'code' => ['coding' => [['code' => '718-7', 'display' => 'Hemoglobina']]],
                    'valueQuantity' => ['value' => 14.2, 'unit' => 'g/dL'],
                    'referenceRange' => [['low' => ['value' => 13.0], 'high' => ['value' => 17.0]]],
                    'interpretation' => [['coding' => [['code' => 'N']]]],
                ],
            ],
            [
                'resource' => [
                    'resourceType' => 'Observation',
                    'id' => 'obs-2',
                    'code' => ['coding' => [['code' => '2345-7', 'display' => 'Glicemia']]],
                    'valueQuantity' => ['value' => 110, 'unit' => 'mg/dL'],
                    'interpretation' => [['coding' => [['code' => 'H']]]],
                ],
            ],
        ];

        $dto = $this->mapper->fromFhir($report, $bundle);

        $this->assertEquals('report-001', $dto->externalId);
        $this->assertEquals('exam-abc', $dto->examinationId);
        $this->assertEquals('final', $dto->status);
        $this->assertCount(2, $dto->results);

        $this->assertEquals('Hemoglobina', $dto->results[0]['name']);
        $this->assertEquals(14.2, $dto->results[0]['value']);
        $this->assertEquals('g/dL', $dto->results[0]['unit']);
        $this->assertEquals('13-17', $dto->results[0]['reference_range']);
        $this->assertEquals('normal', $dto->results[0]['status']);
        $this->assertEquals('718-7', $dto->results[0]['loinc_code']);

        $this->assertEquals('high', $dto->results[1]['status']);
    }

    public function test_report_without_based_on_returns_null_examination_id(): void
    {
        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => 'report-xyz',
            'status' => 'final',
            'result' => [],
        ];

        $dto = $this->mapper->fromFhir($report, []);

        $this->assertNull($dto->examinationId);
    }

    public function test_report_with_based_on_extracts_service_request_id(): void
    {
        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => 'r1',
            'basedOn' => [['reference' => 'ServiceRequest/exam-555']],
            'result' => [],
        ];

        $dto = $this->mapper->fromFhir($report, []);

        $this->assertEquals('exam-555', $dto->examinationId);
    }

    public function test_observation_with_value_string_is_mapped(): void
    {
        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => 'r1',
            'status' => 'final',
            'result' => [['reference' => 'Observation/obs-text']],
        ];

        $bundle = [[
            'resource' => [
                'resourceType' => 'Observation',
                'id' => 'obs-text',
                'code' => ['text' => 'Observação textual'],
                'valueString' => 'Positivo para IgG',
            ],
        ]];

        $dto = $this->mapper->fromFhir($report, $bundle);

        $this->assertEquals('Observação textual', $dto->results[0]['name']);
        $this->assertEquals('Positivo para IgG', $dto->results[0]['value']);
    }

    public function test_interpretation_mapping(): void
    {
        $mappings = [
            'N' => 'normal',
            'H' => 'high',
            'HH' => 'high',
            'L' => 'low',
            'LL' => 'low',
            'A' => 'abnormal',
            'AA' => 'abnormal',
        ];

        foreach ($mappings as $code => $expected) {
            $report = [
                'resourceType' => 'DiagnosticReport',
                'id' => 'r1',
                'status' => 'final',
                'result' => [['reference' => 'Observation/obs-1']],
            ];
            $bundle = [[
                'resource' => [
                    'resourceType' => 'Observation',
                    'id' => 'obs-1',
                    'code' => ['coding' => [['code' => 'x', 'display' => 'test']]],
                    'valueQuantity' => ['value' => 1, 'unit' => 'x'],
                    'interpretation' => [['coding' => [['code' => $code]]]],
                ],
            ]];

            $dto = $this->mapper->fromFhir($report, $bundle);
            $this->assertEquals($expected, $dto->results[0]['status'], "Code '{$code}' should map to '{$expected}'");
        }
    }

    public function test_attachment_url_is_extracted(): void
    {
        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => 'r1',
            'status' => 'final',
            'result' => [],
            'presentedForm' => [
                ['url' => 'https://lab.example.com/reports/abc.pdf', 'contentType' => 'application/pdf'],
            ],
        ];

        $dto = $this->mapper->fromFhir($report, []);

        $this->assertEquals('https://lab.example.com/reports/abc.pdf', $dto->attachmentUrl);
    }

    public function test_accession_number_is_extracted_from_identifier_type(): void
    {
        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => 'r1',
            'status' => 'final',
            'result' => [],
            'identifier' => [
                ['value' => 'some-other-id'],
                ['type' => ['coding' => [['code' => 'ACSN']]], 'value' => 'ACC-12345'],
            ],
        ];

        $dto = $this->mapper->fromFhir($report, []);

        $this->assertEquals('ACC-12345', $dto->accessionNumber);
    }

    public function test_missing_both_id_and_identifier_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DiagnosticReport is missing both "id" and "identifier[0].value".');

        $report = [
            'resourceType' => 'DiagnosticReport',
            'status' => 'final',
            'result' => [],
        ];

        $this->mapper->fromFhir($report, []);
    }

    public function test_effective_datetime_is_preferred_over_issued_for_completed_at(): void
    {
        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => 'r1',
            'status' => 'final',
            'result' => [],
            'effectiveDateTime' => '2026-04-15T10:00:00Z',
            'issued' => '2026-04-16T10:00:00Z',
        ];

        $dto = $this->mapper->fromFhir($report, []);

        $this->assertEquals('2026-04-15T10:00:00Z', $dto->completedAt);
    }

    public function test_issued_fallback_when_effective_datetime_missing(): void
    {
        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => 'r1',
            'status' => 'final',
            'result' => [],
            'issued' => '2026-04-16T10:00:00Z',
        ];

        $dto = $this->mapper->fromFhir($report, []);

        $this->assertEquals('2026-04-16T10:00:00Z', $dto->completedAt);
    }

    public function test_observation_reference_range_without_both_bounds_is_skipped(): void
    {
        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => 'r1',
            'status' => 'final',
            'result' => [['reference' => 'Observation/o1']],
        ];
        $bundle = [[
            'resource' => [
                'resourceType' => 'Observation',
                'id' => 'o1',
                'code' => ['coding' => [['code' => 'x', 'display' => 'test']]],
                'valueQuantity' => ['value' => 5, 'unit' => 'mg/dL'],
                'referenceRange' => [['low' => ['value' => 1]]], // sem high
            ],
        ]];

        $dto = $this->mapper->fromFhir($report, $bundle);

        $this->assertArrayNotHasKey('reference_range', $dto->results[0]);
    }

    public function test_extracts_id_from_identifier_fallback_when_id_missing(): void
    {
        $report = [
            'resourceType' => 'DiagnosticReport',
            'status' => 'final',
            'identifier' => [['value' => 'fallback-id-123']],
            'result' => [],
        ];

        $dto = $this->mapper->fromFhir($report, []);

        $this->assertEquals('fallback-id-123', $dto->externalId);
    }
}
