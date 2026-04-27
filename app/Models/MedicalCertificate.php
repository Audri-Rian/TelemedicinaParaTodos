<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalCertificate extends Model
{
    /** @use HasFactory<\Database\Factories\MedicalCertificateFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'type',
        'start_date',
        'end_date',
        'days',
        'reason',
        'restrictions',
        'signature_hash',
        'signature_status',
        'signed_at',
        'crm_number',
        'verification_code',
        'pdf_url',
        'status',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public const TYPE_ABSENCE = 'absence';

    public const TYPE_ATTENDANCE = 'attendance';

    public const TYPE_DISABILITY = 'disability';

    public const TYPE_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const SIGNATURE_UNSIGNED = 'unsigned';

    public const SIGNATURE_SIGNED = 'signed';

    public const SIGNATURE_VERIFIED = 'verified';

    public const SIGNATURE_INVALID = 'invalid';

    public function isSigned(): bool
    {
        return in_array($this->signature_status, [self::SIGNATURE_SIGNED, self::SIGNATURE_VERIFIED], true);
    }

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
