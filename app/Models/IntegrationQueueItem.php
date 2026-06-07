<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class IntegrationQueueItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'integration_queue';

    protected $fillable = [
        'partner_integration_id',
        'integration_event_id',
        'operation',
        'payload',
        'status',
        'attempts',
        'max_attempts',
        'scheduled_at',
        'started_at',
        'completed_at',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Constantes de status
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    // Constantes de operação
    public const OP_SEND_EXAM_ORDER = 'send_exam_order';
    public const OP_FETCH_EXAM_RESULT = 'fetch_exam_result';
    public const OP_SEND_PRESCRIPTION = 'send_prescription';
    public const OP_VERIFY_PRESCRIPTION = 'verify_prescription';
    public const OP_SUBMIT_RNDS = 'submit_rnds';

    // Relacionamentos

    public function partnerIntegration(): BelongsTo
    {
        return $this->belongsTo(PartnerIntegration::class);
    }

    public function integrationEvent(): BelongsTo
    {
        return $this->belongsTo(IntegrationEvent::class);
    }

    // Scopes

    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_QUEUED)
              ->where(function ($q) {
                  $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
              });
    }

    public function scopeByPartner(Builder $query, string $partnerId): void
    {
        $query->where('partner_integration_id', $partnerId);
    }

    // Métodos

    public function hasReachedMaxAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => $this->hasReachedMaxAttempts() ? self::STATUS_FAILED : self::STATUS_QUEUED,
            'attempts' => $this->attempts + 1,
            'last_error' => $error,
        ]);
    }
}
