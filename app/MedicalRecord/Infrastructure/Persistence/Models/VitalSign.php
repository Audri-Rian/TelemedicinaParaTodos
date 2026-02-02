<?php

namespace App\MedicalRecord\Infrastructure\Persistence\Models;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalSign extends Model
{
    /** @use HasFactory<\Database\Factories\VitalSignFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'doctor_id',
        'recorded_at',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'temperature',
        'heart_rate',
        'respiratory_rate',
        'oxygen_saturation',
        'weight',
        'height',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'metadata' => 'array',
    ];

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
