<?php

namespace App\Integrations\Mappers;

use App\Models\Diagnosis;

/**
 * Mapeia Diagnosis (modelo interno) ↔ FHIR Condition.
 */
class DiagnosisFhirMapper
{
    /**
     * Converte Diagnosis do modelo interno para FHIR Condition.
     */
    public function toFhir(Diagnosis $diagnosis): array
    {
        $coding = ($diagnosis->cid10_code || $diagnosis->cid10_description)
            ? [
                [
                    'system' => 'http://hl7.org/fhir/sid/icd-10',
                    'code' => $diagnosis->cid10_code,
                    'display' => $diagnosis->cid10_description,
                ],
            ]
            : [];

        $result = [
            'resourceType' => 'Condition',
            'identifier' => [
                [
                    'system' => config('integrations.fhir.system_url') . '/diagnosis-id',
                    'value' => $diagnosis->id,
                ],
            ],
            'code' => [
                'coding' => $coding,
            ],
            'subject' => [
                'reference' => "Patient/{$diagnosis->patient_id}",
            ],
            'encounter' => [
                'reference' => "Encounter/{$diagnosis->appointment_id}",
            ],
            'recorder' => [
                'reference' => "Practitioner/{$diagnosis->doctor_id}",
            ],
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                            'code' => 'encounter-diagnosis',
                        ],
                    ],
                ],
            ],
            'note' => $diagnosis->description ? [
                ['text' => $diagnosis->description],
            ] : null,
        ];

        return array_filter($result, fn ($v) => $v !== null);
    }
}
