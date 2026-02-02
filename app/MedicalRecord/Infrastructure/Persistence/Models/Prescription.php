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

class Prescription extends Model
{
    /** @use HasFactory<\Database\Factories\PrescriptionFactory> */
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'medications',
        'instructions',
        'valid_until',
        'status',
        'metadata',
        'issued_at',
        'signature_hash',
        'verification_code',
    ];

    protected $casts = [
        'medications' => 'array',
        'metadata' => 'array',
        'issued_at' => 'datetime',
        'valid_until' => 'date',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
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
