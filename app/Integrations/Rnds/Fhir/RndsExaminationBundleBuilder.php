<?php

namespace App\Integrations\Rnds\Fhir;

use App\Integrations\DTOs\FhirBundleDto;
use App\Integrations\Mappers\PatientFhirMapper;
use App\Models\Examination;
use Illuminate\Support\Str;

class RndsExaminationBundleBuilder
{
    public function buildForExamination(
        Examination $examination,
        PatientFhirMapper $patientMapper,
    ): FhirBundleDto {
        $bundleId = (string) Str::uuid();

        $entries = [];

        if ($examination->patient) {
            $entries[] = ['resource' => $patientMapper->toFhir($examination->patient)];
        }

        $practitioner = $this->buildPractitioner($examination);
        if ($practitioner !== null) {
            $entries[] = ['resource' => $practitioner];
        }

        if ($examination->appointment_id) {
            $entries[] = ['resource' => $this->buildEncounter($examination)];
        }

        [$report, $observations] = $this->buildDiagnosticReportAndObservations($examination);
        $entries[] = ['resource' => $report];
        foreach ($observations as $observation) {
            $entries[] = ['resource' => $observation];
        }

        return new FhirBundleDto(
            type: 'document',
            entries: $entries,
            bundleId: $bundleId,
        );
    }

    private function buildPractitioner(Examination $examination): ?array
    {
        $doctor = $examination->doctor;

        if (! $doctor) {
            return null;
        }

        $resource = [
            'resourceType' => 'Practitioner',
            'id' => $doctor->id,
            'identifier' => [],
            'name' => [['text' => $doctor->user?->name ?? '']],
        ];

        if ($doctor->cns) {
            $resource['identifier'][] = [
                'system' => 'http://rnds.saude.gov.br/fhir/r4/NamingSystem/cns',
                'value' => $doctor->cns,
            ];
        }

        return $resource;
    }

    private function buildEncounter(Examination $examination): array
    {
        return [
            'resourceType' => 'Encounter',
            'id' => $examination->appointment_id,
            'status' => 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject' => [
                'reference' => "Patient/{$examination->patient_id}",
            ],
        ];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    private function buildDiagnosticReportAndObservations(Examination $examination): array
    {
        $reportId = 'report-'.$examination->id;
        $observations = [];
        $observationRefs = [];

        foreach (($examination->results ?? []) as $index => $result) {
            $obsId = "obs-{$examination->id}-{$index}";
            $observationRefs[] = ['reference' => "Observation/{$obsId}"];

            $observations[] = [
                'resourceType' => 'Observation',
                'id' => $obsId,
                'status' => 'final',
                'code' => [
                    'coding' => isset($result['loinc_code']) ? [[
                        'system' => 'http://loinc.org',
                        'code' => $result['loinc_code'],
                        'display' => $result['name'] ?? 'Resultado',
                    ]] : [],
                    'text' => $result['name'] ?? 'Resultado',
                ],
                'subject' => ['reference' => "Patient/{$examination->patient_id}"],
                'valueQuantity' => is_numeric($result['value'] ?? null) ? [
                    'value' => $result['value'],
                    'unit' => $result['unit'] ?? '',
                ] : null,
                'valueString' => ! is_numeric($result['value'] ?? null) ? ($result['value'] ?? null) : null,
            ];
        }

        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => $reportId,
            'status' => 'final',
            'code' => ['text' => $examination->name],
            'subject' => ['reference' => "Patient/{$examination->patient_id}"],
            'effectiveDateTime' => $examination->completed_at?->toIso8601String(),
            'issued' => now()->toIso8601String(),
            'result' => $observationRefs,
        ];

        if ($examination->attachment_url) {
            $report['presentedForm'] = [[
                'url' => $examination->attachment_url,
                'contentType' => 'application/pdf',
            ]];
        }

        $observations = array_map(
            fn ($obs) => array_filter($obs, fn ($v) => $v !== null),
            $observations
        );

        return [$report, $observations];
    }
}
