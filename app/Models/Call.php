<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Call extends Model
{
    use HasUuids;

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'status',
        'requested_at',
        'accepted_at',
        'ended_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_RINGING = 'ringing';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ENDED = 'ended';
    public const STATUS_MISSED = 'missed';

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointments::class, 'appointment_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function room(): HasOne
    {
        return $this->hasOne(Room::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_REQUESTED, self::STATUS_RINGING, self::STATUS_ACCEPTED], true);
    }
}
