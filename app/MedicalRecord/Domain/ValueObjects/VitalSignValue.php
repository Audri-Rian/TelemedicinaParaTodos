<?php

namespace App\MedicalRecord\Domain\ValueObjects;

/**
 * Value Object para conjunto de sinais vitais (temperatura, pressão, etc.).
 * Centraliza regras e limites para valores de sinais vitais.
 */
final readonly class VitalSignValue
{
    public function __construct(
        public ?float $temperature = null,
        public ?int $bloodPressureSystolic = null,
        public ?int $bloodPressureDiastolic = null,
        public ?int $heartRate = null,
        public ?int $respiratoryRate = null,
        public ?float $oxygenSaturation = null,
        public ?float $weight = null,
        public ?float $height = null,
    ) {
        if ($temperature !== null && ($temperature < 30 || $temperature > 45)) {
            throw new \InvalidArgumentException('Temperatura fora do intervalo esperado (30-45 °C).');
        }
        if ($bloodPressureSystolic !== null && ($bloodPressureSystolic < 0 || $bloodPressureSystolic > 300)) {
            throw new \InvalidArgumentException('Pressão sistólica fora do intervalo esperado.');
        }
        if ($bloodPressureDiastolic !== null && ($bloodPressureDiastolic < 0 || $bloodPressureDiastolic > 200)) {
            throw new \InvalidArgumentException('Pressão diastólica fora do intervalo esperado.');
        }
        if ($heartRate !== null && ($heartRate < 0 || $heartRate > 300)) {
            throw new \InvalidArgumentException('Frequência cardíaca fora do intervalo esperado.');
        }
        if ($oxygenSaturation !== null && ($oxygenSaturation < 0 || $oxygenSaturation > 100)) {
            throw new \InvalidArgumentException('Saturação de oxigênio deve estar entre 0 e 100.');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'temperature' => $this->temperature,
            'blood_pressure_systolic' => $this->bloodPressureSystolic,
            'blood_pressure_diastolic' => $this->bloodPressureDiastolic,
            'heart_rate' => $this->heartRate,
            'respiratory_rate' => $this->respiratoryRate,
            'oxygen_saturation' => $this->oxygenSaturation,
            'weight' => $this->weight,
            'height' => $this->height,
        ], fn ($v) => $v !== null);
    }
}
