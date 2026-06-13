<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Call extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'call_type',
    ];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function createFromSystem(array $attributes): static
    {
        return static::forceCreate($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateFromSystem(array $attributes): bool
    {
        return $this->forceFill($attributes)->save();
    }

    protected $casts = [
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'doctor_joined_at' => 'datetime',
        'patient_joined_at' => 'datetime',
        'doctor_left_at' => 'datetime',
        'patient_left_at' => 'datetime',
        'doctor_last_seen_at' => 'datetime',
        'patient_last_seen_at' => 'datetime',
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

    public const CLOSED_REASON_ENDED_BY_DOCTOR = 'ended_by_doctor';

    public const CLOSED_REASON_WINDOW_EXPIRED = 'window_expired';

    public const CLOSED_REASON_ROOM_INACTIVE = 'room_inactive';

    public const CLOSED_REASON_DOCTOR_DISCONNECTED = 'doctor_disconnected';

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
