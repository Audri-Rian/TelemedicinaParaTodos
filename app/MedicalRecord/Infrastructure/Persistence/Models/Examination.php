<?php

namespace App\MedicalRecord\Infrastructure\Persistence\Models;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Examination extends Model
{
    /** @use HasFactory<\Database\Factories\ExaminationFactory> */
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'doctor_id',
        'type',
        'name',
        'requested_at',
        'completed_at',
        'results',
        'attachment_url',
        'status',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'results' => 'array',
        'metadata' => 'array',
    ];

    public const TYPE_LAB = 'lab';
    public const TYPE_IMAGE = 'image';
    public const TYPE_OTHER = 'other';

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

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
}
