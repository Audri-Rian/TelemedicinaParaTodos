<?php

namespace App\Scheduling\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'appointment_id',
        'user_id',
        'event',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public const EVENT_CREATED = 'created';
    public const EVENT_CANCELLED = 'cancelled';
    public const EVENT_RESCHEDULED = 'rescheduled';
    public const EVENT_STARTED = 'started';
    public const EVENT_ENDED = 'ended';
    public const EVENT_NO_SHOW = 'no_show';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_DELETED = 'deleted';

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointments::class, 'appointment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
