<?php

namespace App\MedicalRecord\Infrastructure\Persistence\Models;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalDocument extends Model
{
    /** @use HasFactory<\Database\Factories\MedicalDocumentFactory> */
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'doctor_id',
        'uploaded_by',
        'category',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'metadata',
        'visibility',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
    ];

    public const CATEGORY_EXAM = 'exam';
    public const CATEGORY_PRESCRIPTION = 'prescription';
    public const CATEGORY_REPORT = 'report';
    public const CATEGORY_OTHER = 'other';

    public const VISIBILITY_PATIENT = 'patient';
    public const VISIBILITY_DOCTOR = 'doctor';
    public const VISIBILITY_SHARED = 'shared';

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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
