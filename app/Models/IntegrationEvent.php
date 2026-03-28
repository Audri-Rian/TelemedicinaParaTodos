<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class IntegrationEvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'partner_integration_id',
        'direction',
        'event_type',
        'status',
        'resource_type',
        'resource_id',
        'fhir_resource_type',
        'external_id',
        'request_payload',
        'response_payload',
        'http_status',
        'error_message',
        'retry_count',
        'next_retry_at',
        'duration_ms',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'next_retry_at' => 'datetime',
    ];

    // Constantes de direção
    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_INBOUND = 'inbound';

    // Constantes de status
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRYING = 'retrying';

    // Constantes de event_type
    public const EVENT_EXAM_ORDER_SENT = 'exam_order_sent';
    public const EVENT_EXAM_RESULT_RECEIVED = 'exam_result_received';
    public const EVENT_PRESCRIPTION_SENT = 'prescription_sent';
    public const EVENT_PRESCRIPTION_VERIFIED = 'prescription_verified';
    public const EVENT_RNDS_SUBMITTED = 'rnds_submitted';

    // Relacionamentos

    public function partnerIntegration(): BelongsTo
    {
        return $this->belongsTo(PartnerIntegration::class);
    }

    // Scopes

    public function scopeSuccessful(Builder $query): void
    {
        $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed(Builder $query): void
    {
        $query->where('status', self::STATUS_FAILED);
    }

    public function scopeOutbound(Builder $query): void
    {
        $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    public function scopeInbound(Builder $query): void
    {
        $query->where('direction', self::DIRECTION_INBOUND);
    }

    public function scopeForResource(Builder $query, string $type, string $id): void
    {
        $query->where('resource_type', $type)->where('resource_id', $id);
    }

    // Métodos

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canRetry(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_RETRYING]);
    }
}
