<?php

namespace App\Models;

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
        'partner_integration_id',
        'external_id',
    ];

    protected $guarded = [
        'signature_status',
        'verification_code',
        'signed_at',
    ];

    protected $casts = [
        'medications' => 'array',
        'metadata' => 'array',
        'issued_at' => 'datetime',
        'valid_until' => 'date',
        'signed_at' => 'datetime',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const SIGNATURE_UNSIGNED = 'unsigned';

    public const SIGNATURE_SIGNED = 'signed';

    public const SIGNATURE_VERIFIED = 'verified';

    public const SIGNATURE_INVALID = 'invalid';

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

    public function partnerIntegration(): BelongsTo
    {
        return $this->belongsTo(PartnerIntegration::class);
    }

    public function isSigned(): bool
    {
        return in_array($this->signature_status, [self::SIGNATURE_SIGNED, self::SIGNATURE_VERIFIED], true);
    }
}
