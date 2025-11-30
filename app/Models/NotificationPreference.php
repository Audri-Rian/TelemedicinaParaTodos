<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'channel',
        'type',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    // Canais disponíveis
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_IN_APP = 'in_app';
    public const CHANNEL_PUSH = 'push';

    // Tipo especial para todas as notificações
    public const TYPE_ALL = 'all';

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verificar se a preferência está habilitada
     */
    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    /**
     * Verificar se a preferência está desabilitada
     */
    public function isDisabled(): bool
    {
        return $this->enabled === false;
    }
}
