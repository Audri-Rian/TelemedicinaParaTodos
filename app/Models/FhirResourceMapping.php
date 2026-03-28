<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class FhirResourceMapping extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'internal_resource_type',
        'internal_resource_id',
        'fhir_resource_type',
        'fhir_resource_id',
        'fhir_bundle_id',
        'partner_integration_id',
        'version',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    // Constantes de tipos FHIR
    public const FHIR_PATIENT = 'Patient';
    public const FHIR_PRACTITIONER = 'Practitioner';
    public const FHIR_ORGANIZATION = 'Organization';
    public const FHIR_ENCOUNTER = 'Encounter';
    public const FHIR_SERVICE_REQUEST = 'ServiceRequest';
    public const FHIR_DIAGNOSTIC_REPORT = 'DiagnosticReport';
    public const FHIR_OBSERVATION = 'Observation';
    public const FHIR_MEDICATION_REQUEST = 'MedicationRequest';
    public const FHIR_CONDITION = 'Condition';
    public const FHIR_COMPOSITION = 'Composition';

    // Constantes de tipos internos
    public const INTERNAL_PATIENT = 'patient';
    public const INTERNAL_DOCTOR = 'doctor';
    public const INTERNAL_EXAMINATION = 'examination';
    public const INTERNAL_PRESCRIPTION = 'prescription';
    public const INTERNAL_DIAGNOSIS = 'diagnosis';
    public const INTERNAL_APPOINTMENT = 'appointment';
    public const INTERNAL_VITAL_SIGN = 'vital_sign';

    // Relacionamentos

    public function partnerIntegration(): BelongsTo
    {
        return $this->belongsTo(PartnerIntegration::class);
    }

    // Scopes

    public function scopeForResource(Builder $query, string $type, string $id): void
    {
        $query->where('internal_resource_type', $type)
              ->where('internal_resource_id', $id);
    }

    public function scopeForPartner(Builder $query, string $partnerId): void
    {
        $query->where('partner_integration_id', $partnerId);
    }

    public function scopeByFhirType(Builder $query, string $fhirType): void
    {
        $query->where('fhir_resource_type', $fhirType);
    }

    // Métodos estáticos

    public static function findMapping(string $internalType, string $internalId, ?string $partnerId = null): ?self
    {
        return static::query()
            ->where('internal_resource_type', $internalType)
            ->where('internal_resource_id', $internalId)
            ->when($partnerId, fn ($q) => $q->where('partner_integration_id', $partnerId))
            ->first();
    }

    public static function alreadySynced(string $internalType, string $internalId, string $partnerId): bool
    {
        return static::query()
            ->where('internal_resource_type', $internalType)
            ->where('internal_resource_id', $internalId)
            ->where('partner_integration_id', $partnerId)
            ->exists();
    }
}
