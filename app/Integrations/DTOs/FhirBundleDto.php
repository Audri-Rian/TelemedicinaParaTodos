<?php

namespace App\Integrations\DTOs;

/**
 * Representação simplificada de um FHIR Bundle para transporte interno.
 */
final readonly class FhirBundleDto
{
    /**
     * @param  array<int, array{resourceType?: string, resource?: array}|array>  $entries
     */
    public function __construct(
        public string $type,
        public array $entries,
        public ?string $bundleId = null,
    ) {}

    /**
     * Converte para o formato JSON FHIR.
     */
    public function toFhirJson(): array
    {
        $result = [
            'resourceType' => 'Bundle',
            'type' => $this->type,
            'entry' => array_map(fn (array $entry) => [
                'resource' => $entry['resource'] ?? $entry,
            ], $this->entries),
        ];

        if ($this->bundleId !== null) {
            $result['id'] = $this->bundleId;
        }

        return $result;
    }
}
