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
        'call_type',
        'appointment_id',
        'doctor_id',
        'patient_id',
        'status',
        'requested_at',
        'accepted_at',
        'doctor_joined_at',
        'patient_joined_at',
        'ended_at',
        'call_closed_reason',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'doctor_joined_at' => 'datetime',
        'patient_joined_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public const TYPE_SCHEDULED = 'scheduled';

    public const TYPE_AD_HOC = 'ad_hoc';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_RINGING = 'ringing';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ENDED = 'ended';

    public const STATUS_MISSED = 'missed';

    public const CLOSED_REASON_NO_SHOW = 'no_show';

    public const CLOSED_REASON_DOCTOR_NO_SHOW = 'doctor_no_show';

    public const CLOSED_REASON_PATIENT_NO_SHOW = 'patient_no_show';

    public const CLOSED_REASON_ENDED_BY_USER = 'ended_by_user';

    public const CLOSED_REASON_WINDOW_EXPIRED = 'window_expired';

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
