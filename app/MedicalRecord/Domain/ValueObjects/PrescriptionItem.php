<?php

namespace App\MedicalRecord\Domain\ValueObjects;

/**
 * Value Object para item de prescrição (medicamento, dosagem, frequência).
 * Estrutura imutável para um item do array medications em Prescription.
 */
final readonly class PrescriptionItem
{
    private string $name;

    public function __construct(
        string $name,
        public ?string $dosage = null,
        public ?string $frequency = null,
        public ?string $duration = null,
        public ?string $instructions = null,
    ) {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Nome do medicamento é obrigatório.');
        }
        $this->name = $trimmed;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
            'instructions' => $this->instructions,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            dosage: $data['dosage'] ?? null,
            frequency: $data['frequency'] ?? null,
            duration: $data['duration'] ?? null,
            instructions: $data['instructions'] ?? null,
        );
    }
}
