<?php

namespace App\Integrations\Mappers;

use App\Integrations\DTOs\ExamResultDto;

/**
 * Mapeia FHIR DiagnosticReport + Observation[] → ExamResultDto.
 */
class ExamResultFhirMapper
{
    /**
     * Converte um DiagnosticReport FHIR (+ Observations do Bundle) em DTO interno.
     */
    public function fromFhir(array $diagnosticReport, array $bundleEntries = []): ExamResultDto
    {
        $observations = $this->extractObservations($diagnosticReport, $bundleEntries);

        return new ExamResultDto(
            externalId: $this->extractId($diagnosticReport),
            examinationId: $this->extractBasedOnId($diagnosticReport),
            status: $diagnosticReport['status'] ?? 'final',
            results: $observations,
            completedAt: $diagnosticReport['effectiveDateTime'] ?? $diagnosticReport['issued'] ?? null,
            attachmentUrl: $this->extractAttachment($diagnosticReport),
            accessionNumber: $this->extractAccession($diagnosticReport),
        );
    }

    /**
     * Extrai Observations referenciadas pelo DiagnosticReport.
     */
    private function extractObservations(array $report, array $bundleEntries): array
    {
        $observationRefs = array_filter(array_map(
            fn ($ref) => $ref['reference'] ?? null,
            $report['result'] ?? []
        ));

        // Buscar Observations no Bundle
        $observations = [];
        foreach ($bundleEntries as $entry) {
            $resource = $entry['resource'] ?? $entry;
            if (($resource['resourceType'] ?? '') !== 'Observation') {
                continue;
            }

            $obsId = $resource['id'] ?? null;
            $fullRef = "Observation/{$obsId}";

            // Incluir se referenciada ou se não há referências específicas
            if (empty($observationRefs) || in_array($fullRef, $observationRefs, true)) {
                $observations[] = $this->mapObservation($resource);
            }
        }

        return $observations;
    }

    /**
     * Mapeia uma Observation FHIR para o formato interno de resultado.
     */
    private function mapObservation(array $observation): array
    {
        $coding = $observation['code']['coding'][0] ?? [];
        $valueQuantity = $observation['valueQuantity'] ?? null;
        $referenceRange = $observation['referenceRange'][0] ?? null;

        $result = [
            'name' => $coding['display'] ?? $observation['code']['text'] ?? 'Desconhecido',
            'value' => $valueQuantity['value'] ?? $observation['valueString'] ?? null,
            'unit' => $valueQuantity['unit'] ?? '',
        ];

        if (isset($coding['code'])) {
            $result['loinc_code'] = $coding['code'];
        }

        if ($referenceRange) {
            $low = $referenceRange['low']['value'] ?? null;
            $high = $referenceRange['high']['value'] ?? null;
            if ($low !== null && $high !== null) {
                $result['reference_range'] = "{$low}-{$high}";
            }
        }

        if (isset($observation['interpretation'][0]['coding'][0]['code'])) {
            $result['status'] = $this->mapInterpretation($observation['interpretation'][0]['coding'][0]['code']);
        }

        return $result;
    }

    private function extractId(array $report): string
    {
        return $report['id']
            ?? $report['identifier'][0]['value']
            ?? throw new \InvalidArgumentException('DiagnosticReport is missing both "id" and "identifier[0].value".');
    }

    private function extractBasedOnId(array $report): ?string
    {
        $basedOn = $report['basedOn'][0]['reference'] ?? null;

        if ($basedOn && str_starts_with($basedOn, 'ServiceRequest/')) {
            return str_replace('ServiceRequest/', '', $basedOn);
        }

        return null;
    }

    private function extractAttachment(array $report): ?string
    {
        return $report['presentedForm'][0]['url'] ?? null;
    }

    private function extractAccession(array $report): ?string
    {
        foreach ($report['identifier'] ?? [] as $identifier) {
            if (($identifier['type']['coding'][0]['code'] ?? '') === 'ACSN') {
                return $identifier['value'];
            }
        }

        return null;
    }

    private function mapInterpretation(string $code): string
    {
        return match ($code) {
            'N' => 'normal',
            'H', 'HH' => 'high',
            'L', 'LL' => 'low',
            'A', 'AA' => 'abnormal',
            default => $code,
        };
    }
}
