<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Doctor extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'crm',
        'specialty',
        'biography',
        'license_number',
        'license_expiry_date',
        'status',
        'availability_schedule',
        'consultation_fee',
    ];

    protected $casts = [
        'availability_schedule' => 'array',
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

    // Scopes para filtros
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeBySpecialty(Builder $query, string $specialty): void
    {
        $query->where('specialty', $specialty);
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

    public function isLicenseExpired(): bool
    {
        return $this->license_expiry_date && $this->license_expiry_date->isPast();
    }

    public function isAvailable(): bool
    {
        return $this->isActive() && 
               $this->availability_schedule && 
               !$this->isLicenseExpired();
    }

    // Accessors
    public function getFormattedConsultationFeeAttribute(): string
    {
        return $this->consultation_fee 
            ? 'R$ ' . number_format($this->consultation_fee, 2, ',', '.')
            : 'Não informado';
    }

    public function getFormattedLicenseExpiryDateAttribute(): string
    {
        return $this->license_expiry_date 
            ? $this->license_expiry_date->format('d/m/Y')
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