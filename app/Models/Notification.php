<?php

namespace App\Models;

use App\Casts\SafeNotificationTypeCast;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'metadata',
        'read_at',
    ];

    protected $casts = [
        'type' => SafeNotificationTypeCast::class,
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar notificação como lida
     */
    public function markAsRead(): bool
    {
        if ($this->read_at === null) {
            return $this->update(['read_at' => now()]);
        }
        return false;
    }

    /**
     * Marcar notificação como não lida
     */
    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    /**
     * Verificar se está lida
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Verificar se está não lida
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
