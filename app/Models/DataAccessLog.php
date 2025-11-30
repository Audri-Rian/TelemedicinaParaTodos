<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para log de acessos a dados pessoais (LGPD)
 */
class DataAccessLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'data_subject_id',
        'data_type',
        'resource_id',
        'action',
        'ip_address',
        'user_agent',
        'reason',
        'accessed_fields',
    ];

    protected $casts = [
        'accessed_fields' => 'array',
        'created_at' => 'datetime',
    ];

    // Tipos de dados
    public const DATA_TYPE_MEDICAL_RECORD = 'medical_record';
    public const DATA_TYPE_PERSONAL_DATA = 'personal_data';
    public const DATA_TYPE_CONSULTATION = 'consultation';
    public const DATA_TYPE_PRESCRIPTION = 'prescription';
    public const DATA_TYPE_EXAMINATION = 'examination';

    // Ações
    public const ACTION_VIEW = 'view';
    public const ACTION_EXPORT = 'export';
    public const ACTION_DOWNLOAD = 'download';
    public const ACTION_DELETE = 'delete';
    public const ACTION_UPDATE = 'update';

    /**
     * Relacionamento com User (quem acessou)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento com User (dono dos dados)
     */
    public function dataSubject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'data_subject_id');
    }

    /**
     * Scope para acessos de um usuário específico
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('data_subject_id', $userId);
    }

    /**
     * Scope para acessos por tipo de dado
     */
    public function scopeOfDataType($query, string $dataType)
    {
        return $query->where('data_type', $dataType);
    }
}

