<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Doctor extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'crm',
        'biography',
        'language',
        'license_number',
        'license_expiry_date',
        'status',
        'availability_schedule',
        'consultation_fee',
    ];

    protected $casts = [
        'availability_schedule' => 'array',
        'language' => 'array',
        'license_expiry_date' => 'date',
        'consultation_fee' => 'decimal:2',
        'status' => 'string',
    ];

    // Constantes para status
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    // Relacionamento com User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacionamento N:N com Specialization
    public function specializations()
    {
        return $this->belongsToMany(Specialization::class, 'doctor_specialization')
                    ->withTimestamps();
    }

    // Relacionamento com ServiceLocation
    public function serviceLocations(): HasMany
    {
        return $this->hasMany(ServiceLocation::class, 'doctor_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointments::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(Examination::class);
    }

    public function medicalDocuments(): HasMany
    {
        return $this->hasMany(MedicalDocument::class);
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function clinicalNotes(): HasMany
    {
        return $this->hasMany(ClinicalNote::class);
    }

    public function medicalCertificates(): HasMany
    {
        return $this->hasMany(MedicalCertificate::class);
    }

    // Relacionamento com AvailabilitySlot
    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class, 'doctor_id');
    }

    // Relacionamento com BlockedDate
    public function blockedDates(): HasMany
    {
        return $this->hasMany(\App\Models\Doctor\BlockedDate::class, 'doctor_id');
    }

    // Scopes para filtros
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeBySpecialization(Builder $query, $specializationId): void
    {
        $query->whereHas('specializations', function ($specializationQuery) use ($specializationId) {
            if (is_array($specializationId)) {
                $specializationQuery->whereIn('specializations.id', $specializationId);
            } else {
                $specializationQuery->where('specializations.id', $specializationId);
            }
        });
    }

    public function scopeBySpecializationName(Builder $query, string $specializationName): void
    {
        $query->whereHas('specializations', function ($specializationQuery) use ($specializationName) {
            $specializationQuery->where('name', 'like', '%' . $specializationName . '%');
        });
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE)
              ->whereNotNull('availability_schedule');
    }

    // Métodos de verificação
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasSpecialization(string $specializationName): bool
    {
        return $this->specializations()->where('name', $specializationName)->exists();
    }

    public function hasAnySpecialization(): bool
    {
        return $this->specializations()->exists();
    }

    public function isLicenseExpired(): bool
    {
        return $this->license_expiry_date && $this->license_expiry_date < now();
    }

    public function isAvailable(): bool
    {
        return $this->isActive() && 
               $this->availability_schedule && 
               !$this->isLicenseExpired();
    }

    // Accessors
    public function getSpecializationNamesAttribute(): string
    {
        return $this->specializations->pluck('name')->implode(', ') ?: 'Não informado';
    }

    public function getFormattedConsultationFeeAttribute(): string
    {
        return $this->consultation_fee 
            ? 'R$ ' . number_format((float) $this->consultation_fee, 2, ',', '.')
            : 'Não informado';
    }

    public function getFormattedLicenseExpiryDateAttribute(): string
    {
        return $this->license_expiry_date 
            ? Carbon::parse($this->license_expiry_date)->format('d/m/Y')
            : 'Não informado';
    }

    // Mutators
    public function setConsultationFeeAttribute($value): void
    {
        $this->attributes['consultation_fee'] = $value ? (float) $value : null;
    }

    public function setCrmAttribute($value): void
    {
        $this->attributes['crm'] = strtoupper(trim($value));
    }

    // Validações
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Ativo',
            self::STATUS_INACTIVE => 'Inativo',
            self::STATUS_SUSPENDED => 'Suspenso',
        ];
    }

    // Boot method para configurações automáticas
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($doctor) {
            if (!$doctor->status) {
                $doctor->status = self::STATUS_ACTIVE;
            }
        });
    }
}