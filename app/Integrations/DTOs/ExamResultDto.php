<?php

namespace App\Integrations\DTOs;

/**
 * Dados de um resultado de exame recebido do laboratório.
 *
 * O adapter traduz do formato do parceiro (FHIR DiagnosticReport ou proprietário)
 * para este DTO, que o IntegrationService usa para atualizar o prontuário.
 */
final readonly class ExamResultDto
{
    /**
     * @param  array<int, array{name: string, value: mixed, unit: string, reference_range?: string, status?: string, loinc_code?: string}>  $results
     */
    public function __construct(
        public string $externalId,
        public ?string $examinationId,
        public string $status,
        public array $results,
        public ?string $completedAt = null,
        public ?string $attachmentUrl = null,
        public ?string $accessionNumber = null,
    ) {}
}
