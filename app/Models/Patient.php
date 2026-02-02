<?php

namespace App\Models;

use App\MedicalRecord\Infrastructure\Persistence\Models\ClinicalNote;
use App\MedicalRecord\Infrastructure\Persistence\Models\Diagnosis;
use App\MedicalRecord\Infrastructure\Persistence\Models\Examination;
use App\MedicalRecord\Infrastructure\Persistence\Models\MedicalCertificate;
use App\MedicalRecord\Infrastructure\Persistence\Models\MedicalRecordAuditLog;
use App\MedicalRecord\Infrastructure\Persistence\Models\MedicalDocument;
use App\MedicalRecord\Infrastructure\Persistence\Models\Prescription;
use App\MedicalRecord\Infrastructure\Persistence\Models\VitalSign;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Patient extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'gender',
        'date_of_birth',
        'phone_number',
        'emergency_contact',
        'emergency_phone',
        'medical_history',
        'allergies',
        'current_medications',
        'blood_type',
        'height',
        'weight',
        'insurance_provider',
        'insurance_number',
        'status',
        'consent_telemedicine',
        'last_consultation_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'consent_telemedicine' => 'boolean',
        'last_consultation_at' => 'datetime',
    ];

    // Constantes para enums
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BLOCKED = 'blocked';

    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_OTHER = 'other';

    public const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    // Relacionamento com User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointments::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(Examination::class);
    }

    public function medicalDocuments(): HasMany
    {
        return $this->hasMany(MedicalDocument::class);
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function clinicalNotes(): HasMany
    {
        return $this->hasMany(ClinicalNote::class);
    }

    public function medicalCertificates(): HasMany
    {
        return $this->hasMany(MedicalCertificate::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(MedicalRecordAuditLog::class);
    }

    // Scopes para filtros
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByGender(Builder $query, string $gender): void
    {
        $query->where('gender', $gender);
    }

    public function scopeWithConsent(Builder $query): void
    {
        $query->where('consent_telemedicine', true);
    }

    public function scopeByAgeRange(Builder $query, int $minAge, int $maxAge): void
    {
        $maxDate = Carbon::now()->subYears($minAge);
        $minDate = Carbon::now()->subYears($maxAge + 1);
        
        $query->whereBetween('date_of_birth', [$minDate, $maxDate]);
    }

    public function scopeByBloodType(Builder $query, string $bloodType): void
    {
        $query->where('blood_type', $bloodType);
    }

    public function scopeRecentlyConsulted(Builder $query, int $days = 30): void
    {
        $query->where('last_consultation_at', '>=', Carbon::now()->subDays($days));
    }

    // Accessors
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getFormattedHeightAttribute(): string
    {
        if (!$this->height) return 'Não informado';
        
        $meters = $this->height / 100;
        return number_format($meters, 2, ',', '.') . ' m';
    }

    public function getFormattedWeightAttribute(): string
    {
        return $this->weight 
            ? number_format($this->weight, 1, ',', '.') . ' kg'
            : 'Não informado';
    }

    public function getFormattedPhoneNumberAttribute(): string
    {
        if (!$this->phone_number) return 'Não informado';
        
        // Formatação brasileira: (11) 99999-9999
        $phone = preg_replace('/[^0-9]/', '', $this->phone_number);
        
        if (strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 5) . '-' . 
                   substr($phone, 7);
        }
        
        return $this->phone_number;
    }

    public function getFormattedDateOfBirthAttribute(): string
    {
        return $this->date_of_birth 
            ? $this->date_of_birth->format('d/m/Y')
            : 'Não informado';
    }

    public function getBmiAttribute(): ?float
    {
        if (!$this->height || !$this->weight) return null;
        
        $heightInMeters = $this->height / 100;
        return round($this->weight / ($heightInMeters * $heightInMeters), 2);
    }

    public function getBmiCategoryAttribute(): ?string
    {
        $bmi = $this->bmi;
        if (!$bmi) return null;

        if ($bmi < 18.5) return 'Abaixo do peso';
        if ($bmi < 25) return 'Peso normal';
        if ($bmi < 30) return 'Sobrepeso';
        if ($bmi < 35) return 'Obesidade grau I';
        if ($bmi < 40) return 'Obesidade grau II';
        return 'Obesidade grau III';
    }

    // Mutators
    public function setPhoneNumberAttribute($value): void
    {
        $this->attributes['phone_number'] = $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    public function setEmergencyPhoneAttribute($value): void
    {
        $this->attributes['emergency_phone'] = $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    public function setHeightAttribute($value): void
    {
        $this->attributes['height'] = $value ? (float) $value : null;
    }

    public function setWeightAttribute($value): void
    {
        $this->attributes['weight'] = $value ? (float) $value : null;
    }

    public function setDateOfBirthAttribute($value): void
    {
        if ($value) {
            $this->attributes['date_of_birth'] = Carbon::parse($value);
        }
    }

    // Boot method para configurações automáticas
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($patient) {
            if (!$patient->status) {
                $patient->status = self::STATUS_ACTIVE;
            }
        });
    }

    // Get route key name
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}