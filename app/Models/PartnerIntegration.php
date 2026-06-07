<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerIntegration extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'status',
        'base_url',
        'webhook_url',
        'capabilities',
        'settings',
        'fhir_version',
        'contact_email',
        'contact_phone',
        'connected_at',
        'last_sync_at',
        'connected_by',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'settings' => 'array',
        'connected_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    // Constantes de tipo
    public const TYPE_LABORATORY = 'laboratory';

    public const TYPE_PHARMACY = 'pharmacy';

    public const TYPE_HOSPITAL = 'hospital';

    public const TYPE_INSURANCE = 'insurance';

    public const TYPE_RNDS = 'rnds';

    public const TYPE_OTHER = 'other';

    // Constantes de status
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ERROR = 'error';

    public const STATUS_SUSPENDED = 'suspended';

    // Relacionamentos

    public function connectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_partner_integrations')
            ->withPivot([
                'integration_mode',
                'perm_send_orders',
                'perm_receive_results',
                'perm_webhook',
                'perm_patient_data',
                'connected_by',
                'connected_at',
            ])
            ->withTimestamps();
    }

    public function credential(): HasOne
    {
        return $this->hasOne(IntegrationCredential::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(IntegrationEvent::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(IntegrationWebhook::class);
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(IntegrationQueueItem::class);
    }

    public function fhirMappings(): HasMany
    {
        return $this->hasMany(FhirResourceMapping::class);
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(Examination::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    // Scopes

    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function scopeLaboratories(Builder $query): void
    {
        $query->where('type', self::TYPE_LABORATORY);
    }

    public function scopeForDoctor(Builder $query, string $doctorId): void
    {
        $query->whereIn('partner_integrations.id', function ($subQuery) use ($doctorId) {
            $subQuery
                ->select('partner_integration_id')
                ->from('doctor_partner_integrations')
                ->where('doctor_id', $doctorId);
        });
    }

    public function scopePharmacies(Builder $query): void
    {
        $query->where('type', self::TYPE_PHARMACY);
    }

    // Métodos de verificação

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    public function supportsFhir(): bool
    {
        return $this->fhir_version !== null;
    }

    // Accessors

    public function getFormattedLastSyncAttribute(): string
    {
        return $this->last_sync_at
            ? $this->last_sync_at->diffForHumans()
            : 'Nunca sincronizado';
    }

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_LABORATORY => 'Laboratório',
            self::TYPE_PHARMACY => 'Farmácia',
            self::TYPE_HOSPITAL => 'Hospital',
            self::TYPE_INSURANCE => 'Convênio',
            self::TYPE_RNDS => 'RNDS',
            self::TYPE_OTHER => 'Outro',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Ativo',
            self::STATUS_INACTIVE => 'Inativo',
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_ERROR => 'Erro',
            self::STATUS_SUSPENDED => 'Suspenso',
        ];
    }

    // Boot

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($integration) {
            if (! $integration->status) {
                $integration->status = self::STATUS_PENDING;
            }
        });
    }
}
