<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TimelineEvent extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'subtitle',
        'start_date',
        'end_date',
        'description',
        'media_url',
        'extra_data',
        'order_priority',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'extra_data' => 'array',
        'order_priority' => 'integer',
    ];

    // Constantes para tipos
    public const TYPE_EDUCATION = 'education';
    public const TYPE_COURSE = 'course';
    public const TYPE_CERTIFICATE = 'certificate';
    public const TYPE_PROJECT = 'project';

    // Relacionamentos
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByUser(Builder $query, string $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order_priority', 'desc')
              ->orderBy('start_date', 'desc')
              ->orderBy('end_date', 'desc');
    }

    public function scopeInProgress(Builder $query): void
    {
        $query->whereNull('end_date');
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->whereNotNull('end_date');
    }

    // Accessors
    public function getIsInProgressAttribute(): bool
    {
        return $this->end_date === null;
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->start_date) {
            return null;
        }

        // start_date já é castado como Carbon date
        $start = $this->start_date;
        
        if ($this->end_date) {
            // end_date já é castado como Carbon date
            $end = $this->end_date;
            $diffInDays = $start->diffInDays($end);
            
            if ($diffInDays < 30) {
                return $diffInDays . ' ' . ($diffInDays === 1 ? 'dia' : 'dias');
            }
            
            $diffInMonths = $start->diffInMonths($end);
            
            if ($diffInMonths < 12) {
                return $diffInMonths . ' ' . ($diffInMonths === 1 ? 'mês' : 'meses');
            }
            
            $years = floor($diffInMonths / 12);
            $months = $diffInMonths % 12;
            $result = $years . ' ' . ($years === 1 ? 'ano' : 'anos');
            if ($months > 0) {
                $result .= ' e ' . $months . ' ' . ($months === 1 ? 'mês' : 'meses');
            }
            return $result;
        }

        return 'Em andamento desde ' . $start->format('m/Y');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_EDUCATION => 'Educação',
            self::TYPE_COURSE => 'Curso',
            self::TYPE_CERTIFICATE => 'Certificado',
            self::TYPE_PROJECT => 'Projeto',
            default => ucfirst($this->type),
        };
    }

    public function getFormattedStartDateAttribute(): string
    {
        if (!$this->start_date) {
            return '';
        }
        // start_date já é castado como Carbon date
        return $this->start_date->format('d/m/Y');
    }

    public function getFormattedEndDateAttribute(): ?string
    {
        if (!$this->end_date) {
            return null;
        }
        // end_date já é castado como Carbon date
        return $this->end_date->format('d/m/Y');
    }

    public function getDateRangeAttribute(): string
    {
        $start = $this->formatted_start_date;
        $end = $this->formatted_end_date ?? 'Em andamento';
        return $start . ' - ' . $end;
    }

    // Mutators
    public function setOrderPriorityAttribute($value): void
    {
        $this->attributes['order_priority'] = $value ?? 0;
    }

    // Validações estáticas
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_EDUCATION => 'Educação',
            self::TYPE_COURSE => 'Curso',
            self::TYPE_CERTIFICATE => 'Certificado',
            self::TYPE_PROJECT => 'Projeto',
        ];
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, [
            self::TYPE_EDUCATION,
            self::TYPE_COURSE,
            self::TYPE_CERTIFICATE,
            self::TYPE_PROJECT,
        ]);
    }
}
