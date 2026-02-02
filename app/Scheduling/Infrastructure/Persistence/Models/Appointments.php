<?php

namespace App\Scheduling\Infrastructure\Persistence\Models;

use App\MedicalRecord\Infrastructure\Persistence\Models\ClinicalNote;
use App\MedicalRecord\Infrastructure\Persistence\Models\Diagnosis;
use App\MedicalRecord\Infrastructure\Persistence\Models\Examination;
use App\MedicalRecord\Infrastructure\Persistence\Models\MedicalCertificate;
use App\MedicalRecord\Infrastructure\Persistence\Models\MedicalDocument;
use App\MedicalRecord\Infrastructure\Persistence\Models\Prescription;
use App\MedicalRecord\Infrastructure\Persistence\Models\VitalSign;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointments extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'scheduled_at',
        'access_code',
        'started_at',
        'ended_at',
        'video_recording_url',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RESCHEDULED = 'rescheduled';

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function logs()
    {
        return $this->hasMany(AppointmentLog::class, 'appointment_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'appointment_id');
    }

    public function examinations()
    {
        return $this->hasMany(Examination::class, 'appointment_id');
    }

    public function medicalDocuments()
    {
        return $this->hasMany(MedicalDocument::class, 'appointment_id');
    }

    public function vitalSigns()
    {
        return $this->hasMany(VitalSign::class, 'appointment_id');
    }

    public function diagnoses()
    {
        return $this->hasMany(Diagnosis::class, 'appointment_id');
    }

    public function clinicalNotes()
    {
        return $this->hasMany(ClinicalNote::class, 'appointment_id');
    }

    public function medicalCertificates()
    {
        return $this->hasMany(MedicalCertificate::class, 'appointment_id');
    }

    public function logEvent(string $event, ?array $payload = null, ?string $userId = null): AppointmentLog
    {
        return $this->logs()->create([
            'event' => $event,
            'payload' => $payload,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    public function scopeScheduled(Builder $query): void
    {
        $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeInProgress(Builder $query): void
    {
        $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeByDoctor(Builder $query, string $doctorId): void
    {
        $query->where('doctor_id', $doctorId);
    }

    public function scopeByPatient(Builder $query, string $patientId): void
    {
        $query->where('patient_id', $patientId);
    }

    public function scopeToday(Builder $query): void
    {
        $query->whereDate('scheduled_at', Carbon::today());
    }

    public function scopeThisWeek(Builder $query): void
    {
        $query->whereBetween('scheduled_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ]);
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->where('scheduled_at', '>', Carbon::now())
            ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_RESCHEDULED]);
    }

    public function scopePast(Builder $query): void
    {
        $query->where('scheduled_at', '<', Carbon::now());
    }

    public function scopeByDateRange(Builder $query, Carbon $startDate, Carbon $endDate): void
    {
        $query->whereBetween('scheduled_at', [$startDate, $endDate]);
    }

    public function getDurationAttribute(): ?int
    {
        if (! $this->started_at || ! $this->ended_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->ended_at);
    }

    public function getFormattedDurationAttribute(): string
    {
        $duration = $this->duration;
        if (! $duration) {
            return '45min';
        }

        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}min";
        }

        return "{$minutes}min";
    }

    public function setScheduledAtAttribute($value): void
    {
        $this->attributes['scheduled_at'] = Carbon::parse($value);
    }

    public function setStartedAtAttribute($value): void
    {
        $this->attributes['started_at'] = $value ? Carbon::parse($value) : null;
    }

    public function setEndedAtAttribute($value): void
    {
        $this->attributes['ended_at'] = $value ? Carbon::parse($value) : null;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
