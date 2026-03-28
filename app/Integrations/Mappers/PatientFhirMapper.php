<?php

namespace App\Integrations\Mappers;

use App\Models\Patient;

/**
 * Mapeia Patient (modelo interno) ↔ FHIR Patient.
 */
class PatientFhirMapper
{
    /**
     * Converte Patient do modelo interno para FHIR Patient.
     */
    public function toFhir(Patient $patient): array
    {
        $patient->loadMissing('user');

        $identifiers = [
            [
                'system' => config('integrations.fhir.system_url') . '/patient-id',
                'value' => $patient->id,
            ],
        ];

        if ($patient->cns) {
            $identifiers[] = [
                'system' => 'http://rnds.saude.gov.br/fhir/r4/NamingSystem/cns',
                'value' => $patient->cns,
            ];
        }

        if ($patient->cpf) {
            $identifiers[] = [
                'system' => 'http://rnds.saude.gov.br/fhir/r4/NamingSystem/cpf',
                'value' => $patient->cpf,
            ];
        }

        $telecom = [];
        if ($patient->phone_number) {
            $telecom[] = ['system' => 'phone', 'value' => $patient->phone_number];
        }
        if ($patient->user?->email) {
            $telecom[] = ['system' => 'email', 'value' => $patient->user->email];
        }

        $resource = [
            'resourceType' => 'Patient',
            'identifier' => $identifiers,
            'name' => [['text' => $patient->user?->name ?? '']],
            'telecom' => $telecom ?: null,
        ];

        if ($patient->date_of_birth) {
            $resource['birthDate'] = $patient->date_of_birth->format('Y-m-d');
        }

        if ($patient->gender) {
            $resource['gender'] = $this->mapGender($patient->gender);
        }

        if ($patient->mother_name) {
            $resource['extension'] = [
                [
                    'url' => 'http://rnds.saude.gov.br/fhir/r4/StructureDefinition/nome-mae',
                    'valueString' => $patient->mother_name,
                ],
            ];
        }

        return array_filter($resource, fn ($v) => $v !== null);
    }

    private function mapGender(string $gender): string
    {
        return match ($gender) {
            'male' => 'male',
            'female' => 'female',
            default => 'other',
        };
    }
}
