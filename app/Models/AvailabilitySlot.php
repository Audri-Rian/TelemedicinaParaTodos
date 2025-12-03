<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AvailabilitySlot extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'doctor_availability_slots';

    protected $fillable = [
        'doctor_id',
        'location_id',
        'type',
        'day_of_week',
        'specific_date',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'specific_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'is_active' => 'boolean',
    ];

    // Constantes para tipos
    public const TYPE_RECURRING = 'recurring';
    public const TYPE_SPECIFIC = 'specific';

    // Constantes para dias da semana
    public const DAY_MONDAY = 'monday';
    public const DAY_TUESDAY = 'tuesday';
    public const DAY_WEDNESDAY = 'wednesday';
    public const DAY_THURSDAY = 'thursday';
    public const DAY_FRIDAY = 'friday';
    public const DAY_SATURDAY = 'saturday';
    public const DAY_SUNDAY = 'sunday';

    // Relacionamentos
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ServiceLocation::class, 'location_id');
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

    public function scopeRecurring(Builder $query): void
    {
        $query->where('type', self::TYPE_RECURRING);
    }

    public function scopeSpecific(Builder $query): void
    {
        $query->where('type', self::TYPE_SPECIFIC);
    }

    public function scopeByDayOfWeek(Builder $query, string $dayOfWeek): void
    {
        $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeBySpecificDate(Builder $query, Carbon|string $date): void
    {
        $dateString = $date instanceof Carbon ? $date->format('Y-m-d') : $date;
        $query->where('specific_date', $dateString);
    }

    public function scopeByDateRange(Builder $query, Carbon $startDate, Carbon $endDate): void
    {
        $query->where(function ($q) use ($startDate, $endDate) {
            // Slots recorrentes (todos os dias da semana no período)
            $q->where(function ($recurringQuery) use ($startDate, $endDate) {
                $recurringQuery->where('type', self::TYPE_RECURRING)
                    ->where('is_active', true);
            })
            // Slots específicos no período
            ->orWhere(function ($specificQuery) use ($startDate, $endDate) {
                $specificQuery->where('type', self::TYPE_SPECIFIC)
                    ->whereBetween('specific_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('is_active', true);
            });
        });
    }

    // Métodos de verificação
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isRecurring(): bool
    {
        return $this->type === self::TYPE_RECURRING;
    }

    public function isSpecific(): bool
    {
        return $this->type === self::TYPE_SPECIFIC;
    }

    // Métodos auxiliares
    public function matchesDate(Carbon $date): bool
    {
        if ($this->type === self::TYPE_SPECIFIC) {
            return $this->specific_date && $this->specific_date->format('Y-m-d') === $date->format('Y-m-d');
        }

        if ($this->type === self::TYPE_RECURRING) {
            $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.
            return $this->day_of_week === $dayOfWeek;
        }

        return false;
    }

    // Accessors
    public function getDayOfWeekLabelAttribute(): string
    {
        return match($this->day_of_week) {
            self::DAY_MONDAY => 'Segunda-feira',
            self::DAY_TUESDAY => 'Terça-feira',
            self::DAY_WEDNESDAY => 'Quarta-feira',
            self::DAY_THURSDAY => 'Quinta-feira',
            self::DAY_FRIDAY => 'Sexta-feira',
            self::DAY_SATURDAY => 'Sábado',
            self::DAY_SUNDAY => 'Domingo',
            default => $this->day_of_week ?? '',
        };
    }

    /**
     * Accessor para start_time - sempre retorna no formato H:i (sem segundos)
     */
    public function getStartTimeAttribute($value): string
    {
        if (!$value) {
            return '';
        }
        
        // Normalizar formato de hora (remover segundos se existirem)
        $time = is_string($value) ? $value : (string)$value;
        
        // Se tiver segundos (formato H:i:s), remover
        if (strlen($time) > 5 && substr_count($time, ':') === 2) {
            return substr($time, 0, 5);
        }
        
        return $time;
    }

    /**
     * Accessor para end_time - sempre retorna no formato H:i (sem segundos)
     */
    public function getEndTimeAttribute($value): string
    {
        if (!$value) {
            return '';
        }
        
        // Normalizar formato de hora (remover segundos se existirem)
        $time = is_string($value) ? $value : (string)$value;
        
        // Se tiver segundos (formato H:i:s), remover
        if (strlen($time) > 5 && substr_count($time, ':') === 2) {
            return substr($time, 0, 5);
        }
        
        return $time;
    }
}

