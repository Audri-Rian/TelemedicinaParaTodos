<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model para gerenciar consentimentos LGPD
 */
class Consent extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'granted',
        'description',
        'version',
        'granted_at',
        'revoked_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Tipos de consentimento
    public const TYPE_TELEMEDICINE = 'telemedicine';
    public const TYPE_VIDEO_RECORDING = 'video_recording';
    public const TYPE_DATA_PROCESSING = 'data_processing';
    public const TYPE_MARKETING = 'marketing';

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica se o consentimento está ativo
     */
    public function isActive(): bool
    {
        return $this->granted && 
               $this->granted_at !== null && 
               $this->revoked_at === null &&
               $this->deleted_at === null;
    }

    /**
     * Scope para consentimentos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('granted', true)
                     ->whereNotNull('granted_at')
                     ->whereNull('revoked_at');
    }

    /**
     * Scope para tipo específico
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

