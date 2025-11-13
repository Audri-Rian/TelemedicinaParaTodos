<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceLocation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'doctor_service_locations';

    protected $fillable = [
        'doctor_id',
        'name',
        'type',
        'address',
        'phone',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Constantes para tipos
    public const TYPE_TELECONSULTATION = 'teleconsultation';
    public const TYPE_OFFICE = 'office';
    public const TYPE_HOSPITAL = 'hospital';
    public const TYPE_CLINIC = 'clinic';

    // Relacionamentos
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class, 'location_id');
    }

    // Scopes
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeByDoctor(Builder $query, string $doctorId): void
    {
        $query->where('doctor_id', $doctorId);
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    // Métodos de verificação
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isTeleconsultation(): bool
    {
        return $this->type === self::TYPE_TELECONSULTATION;
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_TELECONSULTATION => 'Teleconsulta',
            self::TYPE_OFFICE => 'Consultório',
            self::TYPE_HOSPITAL => 'Hospital',
            self::TYPE_CLINIC => 'Clínica',
            default => $this->type,
        };
    }
}

