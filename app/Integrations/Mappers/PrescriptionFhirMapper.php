<?php

namespace App\Integrations\Mappers;

use App\Models\Prescription;

/**
 * Mapeia Prescription (modelo interno) → FHIR MedicationRequest[].
 *
 * Cada medicamento na prescrição gera um MedicationRequest FHIR separado.
 */
class PrescriptionFhirMapper
{
    /**
     * Converte uma Prescription em array de FHIR MedicationRequest.
     *
     * @return array[] Array de recursos MedicationRequest
     */
    public function toFhir(Prescription $prescription): array
    {
        $prescription->loadMissing(['patient', 'doctor']);

        $medications = $prescription->medications ?? [];
        $resources = [];

        foreach ($medications as $index => $medication) {
            $resources[] = $this->buildMedicationRequest($prescription, $medication, $index);
        }

        return $resources;
    }

    private function buildMedicationRequest(Prescription $prescription, array $medication, int $index): array
    {
        $resource = [
            'resourceType' => 'MedicationRequest',
            'identifier' => [
                [
                    'system' => config('integrations.fhir.system_url') . '/prescription-id',
                    'value' => "{$prescription->id}-{$index}",
                ],
            ],
            'status' => $this->mapStatus($prescription->status),
            'intent' => 'order',
            'medicationCodeableConcept' => [
                'text' => $medication['name'] ?? $medication['medication'] ?? '',
            ],
            'subject' => [
                'reference' => "Patient/{$prescription->patient_id}",
            ],
            'requester' => [
                'reference' => "Practitioner/{$prescription->doctor_id}",
            ],
            'authoredOn' => $prescription->issued_at?->toIso8601String(),
        ];

        if ($prescription->appointment_id) {
            $resource['encounter'] = [
                'reference' => "Encounter/{$prescription->appointment_id}",
            ];
        }

        if (isset($medication['dosage']) || isset($medication['frequency'])) {
            $resource['dosageInstruction'] = [
                [
                    'text' => trim(($medication['dosage'] ?? '') . ' ' . ($medication['frequency'] ?? '')),
                ],
            ];
        }

        if ($prescription->valid_until) {
            $resource['dispenseRequest'] = [
                'validityPeriod' => [
                    'start' => $prescription->issued_at?->format('Y-m-d'),
                    'end' => $prescription->valid_until->format('Y-m-d'),
                ],
            ];
        }

        if ($prescription->instructions) {
            $resource['note'] = [['text' => $prescription->instructions]];
        }

        return $resource;
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'active' => 'active',
            'expired' => 'stopped',
            'cancelled' => 'cancelled',
            default => 'unknown',
        };
    }
}
