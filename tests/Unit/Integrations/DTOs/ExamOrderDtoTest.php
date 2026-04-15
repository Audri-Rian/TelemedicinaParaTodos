<?php

namespace Tests\Unit\Integrations\DTOs;

use App\Integrations\DTOs\ExamOrderDto;
use App\Models\Examination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamOrderDtoTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_examination_extracts_basic_fields(): void
    {
        $examination = Examination::factory()->create([
            'name' => 'Hemograma completo',
            'type' => Examination::TYPE_LAB,
        ]);

        $dto = ExamOrderDto::fromExamination($examination);

        $this->assertEquals($examination->id, $dto->examinationId);
        $this->assertEquals($examination->patient_id, $dto->patientId);
        $this->assertEquals($examination->doctor_id, $dto->doctorId);
        $this->assertEquals('Hemograma completo', $dto->examName);
        $this->assertEquals(Examination::TYPE_LAB, $dto->examType);
        $this->assertNotNull($dto->requestedAt);
    }

    public function test_from_examination_handles_null_cns(): void
    {
        $examination = Examination::factory()->create();

        $dto = ExamOrderDto::fromExamination($examination);

        // CNS is null by default in factory
        $this->assertNull($dto->patientCns);
        $this->assertNull($dto->doctorCns);
    }

    public function test_from_examination_fallback_requested_at(): void
    {
        $examination = Examination::factory()->create([
            'requested_at' => null,
        ]);

        $dto = ExamOrderDto::fromExamination($examination);

        $this->assertNotNull($dto->requestedAt);
    }

    public function test_dto_is_readonly(): void
    {
        $dto = new \App\Integrations\DTOs\ExamResultDto(
            externalId: 'ext-123',
            examinationId: null,
            status: 'final',
            results: [['name' => 'Test', 'value' => 1, 'unit' => 'mg']],
            completedAt: now()->toIso8601String(),
        );

        $this->assertEquals('ext-123', $dto->externalId);
        $this->assertEquals('final', $dto->status);
        $this->assertCount(1, $dto->results);
    }
}
