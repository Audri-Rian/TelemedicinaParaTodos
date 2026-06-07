<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntegrationWebhook extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'partner_integration_id',
        'url',
        'secret',
        'events',
        'status',
        'failure_count',
        'last_triggered_at',
        'last_success_at',
    ];

    protected $casts = [
        'events' => 'array',
        'last_triggered_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
    ];

    // Constantes de status
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FAILED = 'failed';

    public const MAX_FAILURES_BEFORE_DISABLE = 10;

    // Relacionamentos

    public function partnerIntegration(): BelongsTo
    {
        return $this->belongsTo(PartnerIntegration::class);
    }

    // Métodos

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function subscribesToEvent(string $eventType): bool
    {
        return in_array($eventType, $this->events ?? []);
    }

    public function recordFailure(): void
    {
        $this->increment('failure_count');

        if ($this->failure_count >= self::MAX_FAILURES_BEFORE_DISABLE) {
            $this->update(['status' => self::STATUS_FAILED]);
        }
    }

    public function recordSuccess(): void
    {
        $this->update([
            'failure_count' => 0,
            'last_success_at' => now(),
            'last_triggered_at' => now(),
        ]);
    }
}
