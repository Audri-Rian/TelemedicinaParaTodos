<?php

namespace Tests\Unit\Integrations\DTOs;

use App\Integrations\DTOs\ExamResultDto;
use Tests\TestCase;

class ExamResultDtoTest extends TestCase
{
    public function test_constructs_with_all_fields(): void
    {
        $dto = new ExamResultDto(
            externalId: 'ext-123',
            examinationId: 'exam-456',
            status: 'final',
            results: [
                [
                    'name' => 'Hemoglobina',
                    'value' => 14.2,
                    'unit' => 'g/dL',
                    'reference_range' => '13.0-17.0',
                    'status' => 'normal',
                    'loinc_code' => '718-7',
                ],
            ],
            completedAt: '2026-04-15T12:00:00Z',
            attachmentUrl: 'https://lab.example.com/report.pdf',
            accessionNumber: 'ACC-001',
        );

        $this->assertEquals('ext-123', $dto->externalId);
        $this->assertEquals('exam-456', $dto->examinationId);
        $this->assertEquals('final', $dto->status);
        $this->assertCount(1, $dto->results);
        $this->assertEquals('2026-04-15T12:00:00Z', $dto->completedAt);
        $this->assertEquals('https://lab.example.com/report.pdf', $dto->attachmentUrl);
        $this->assertEquals('ACC-001', $dto->accessionNumber);
    }

    public function test_constructs_with_only_required_fields(): void
    {
        $dto = new ExamResultDto(
            externalId: 'ext-001',
            examinationId: null,
            status: 'preliminary',
            results: [],
        );

        $this->assertEquals('ext-001', $dto->externalId);
        $this->assertNull($dto->examinationId);
        $this->assertEquals('preliminary', $dto->status);
        $this->assertSame([], $dto->results);
        $this->assertNull($dto->completedAt);
        $this->assertNull($dto->attachmentUrl);
        $this->assertNull($dto->accessionNumber);
    }

    public function test_is_readonly(): void
    {
        $dto = new ExamResultDto(
            externalId: 'ext-1',
            examinationId: 'exam-1',
            status: 'final',
            results: [],
        );

        $this->expectException(\Error::class);

        /** @phpstan-ignore-next-line */
        $dto->externalId = 'mutado';
    }

    public function test_accepts_complex_result_structure(): void
    {
        $results = [
            [
                'name' => 'Hemoglobina',
                'value' => 14.2,
                'unit' => 'g/dL',
                'reference_range' => '13.0-17.0',
                'status' => 'normal',
                'loinc_code' => '718-7',
            ],
            [
                'name' => 'Glicemia',
                'value' => 180,
                'unit' => 'mg/dL',
                'reference_range' => '70-99',
                'status' => 'high',
                'loinc_code' => '2345-7',
            ],
        ];

        $dto = new ExamResultDto(
            externalId: 'ext-complex',
            examinationId: 'exam-complex',
            status: 'final',
            results: $results,
        );

        $this->assertCount(2, $dto->results);
        $this->assertEquals('high', $dto->results[1]['status']);
        $this->assertEquals('2345-7', $dto->results[1]['loinc_code']);
    }

    public function test_status_accepts_various_fhir_statuses(): void
    {
        $statuses = ['registered', 'partial', 'preliminary', 'final', 'amended', 'corrected', 'appended'];

        foreach ($statuses as $status) {
            $dto = new ExamResultDto(
                externalId: 'ext-' . $status,
                examinationId: null,
                status: $status,
                results: [],
            );
            $this->assertEquals($status, $dto->status);
        }
    }

    public function test_null_examination_id_is_allowed_for_orphan_results(): void
    {
        // Cenário: laboratório envia resultado sem referência a ServiceRequest
        $dto = new ExamResultDto(
            externalId: 'ext-orphan',
            examinationId: null,
            status: 'final',
            results: [['name' => 'Teste', 'value' => 1, 'unit' => 'x']],
        );

        $this->assertNull($dto->examinationId);
    }
}
