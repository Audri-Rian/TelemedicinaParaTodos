<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para auditoria de acessos e ações
 */
class AuditLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'ip_address',
        'user_agent',
        'request_data',
        'response_status',
        'metadata',
    ];

    protected $casts = [
        'request_data' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para filtrar por ação
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope para filtrar por tipo de recurso
     */
    public function scopeResourceType($query, string $type)
    {
        return $query->where('resource_type', $type);
    }

    /**
     * Scope para filtrar por usuário
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para acessos a dados pessoais (LGPD)
     */
    public function scopePersonalDataAccess($query)
    {
        return $query->whereIn('action', [
            'view_patient_record',
            'view_medical_record',
            'export_data',
            'access_personal_data',
        ]);
    }
}

