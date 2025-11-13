<?php

namespace App\Models\Doctor;

use App\Models\Doctor as DoctorModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class BlockedDate extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'doctor_blocked_dates';

    protected $fillable = [
        'doctor_id',
        'blocked_date',
        'reason',
    ];

    protected $casts = [
        'blocked_date' => 'date',
    ];

    // Relacionamentos
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(DoctorModel::class, 'doctor_id');
    }

    // Scopes
    public function scopeByDoctor(Builder $query, string $doctorId): void
    {
        $query->where('doctor_id', $doctorId);
    }

    public function scopeByDate(Builder $query, Carbon|string $date): void
    {
        $dateString = $date instanceof Carbon ? $date->format('Y-m-d') : $date;
        $query->where('blocked_date', $dateString);
    }

    public function scopeByDateRange(Builder $query, Carbon $startDate, Carbon $endDate): void
    {
        $query->whereBetween('blocked_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
    }

    public function scopeFutureDates(Builder $query): void
    {
        $query->where('blocked_date', '>=', Carbon::today()->format('Y-m-d'));
    }
}

