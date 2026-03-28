<?php

namespace App\Integrations\Mappers;

use App\Integrations\DTOs\ExamOrderDto;

/**
 * Mapeia ExamOrderDto (modelo interno) → FHIR ServiceRequest.
 */
class ExamOrderFhirMapper
{
    private const CNS_SYSTEM = 'http://rnds.saude.gov.br/fhir/r4/NamingSystem/cns';

    /**
     * Converte um pedido de exame interno para FHIR ServiceRequest.
     */
    public function toFhir(ExamOrderDto $order): array
    {
        $base = config('integrations.fhir.system_url');
        $identifier = [];
        if ($base) {
            $identifier[] = [
                'system' => rtrim($base, '/') . '/examination-id',
                'value' => $order->examinationId,
            ];
        }

        $noteText = null;
        if ($order->metadata) {
            try {
                $noteText = json_encode($order->metadata, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $noteText = '{}';
            }
        }

        $result = [
            'resourceType' => 'ServiceRequest',
            'identifier' => $identifier,
            'status' => 'active',
            'intent' => 'order',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => $this->mapExamTypeToCategory($order->examType),
                        ],
                    ],
                ],
            ],
            'code' => [
                'text' => $order->examName,
            ],
            'subject' => $this->buildPatientReference($order),
            'requester' => $this->buildPractitionerReference($order),
            'encounter' => $order->appointmentId ? [
                'reference' => "Encounter/{$order->appointmentId}",
            ] : null,
            'authoredOn' => $order->requestedAt,
            'note' => $noteText ? [
                ['text' => $noteText],
            ] : null,
        ];

        return array_filter($result, fn ($v) => $v !== null);
    }

    private function mapExamTypeToCategory(string $type): string
    {
        return match ($type) {
            'lab' => 'laboratory',
            'image' => 'imaging',
            default => 'exam',
        };
    }

    private function buildPatientReference(ExamOrderDto $order): array
    {
        $reference = ['reference' => "Patient/{$order->patientId}"];

        if ($order->patientCns) {
            $reference['identifier'] = [
                'system' => self::CNS_SYSTEM,
                'value' => $order->patientCns,
            ];
        }

        return $reference;
    }

    private function buildPractitionerReference(ExamOrderDto $order): array
    {
        $reference = ['reference' => "Practitioner/{$order->doctorId}"];

        if ($order->doctorCns) {
            $reference['identifier'] = [
                'system' => self::CNS_SYSTEM,
                'value' => $order->doctorCns,
            ];
        }

        return $reference;
    }
}
