<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Message extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'appointment_id',
        'read_at',
        'status',
        'delivered_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Constantes para status
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    /**
     * Relacionamento com o usuário que enviou a mensagem
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Relacionamento com o usuário que recebeu a mensagem
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Relacionamento com appointment (opcional)
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointments::class, 'appointment_id');
    }

    /**
     * Marcar mensagem como lida
     */
    public function markAsRead(): bool
    {
        if ($this->read_at === null) {
            return $this->update(['read_at' => Carbon::now()]);
        }
        return false;
    }

    /**
     * Verificar se a mensagem foi lida
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Marcar mensagem como entregue
     */
    public function markAsDelivered(): bool
    {
        if ($this->status !== self::STATUS_DELIVERED) {
            return $this->update([
                'status' => self::STATUS_DELIVERED,
                'delivered_at' => Carbon::now(),
            ]);
        }
        return false;
    }

    /**
     * Verificar se a mensagem foi entregue
     */
    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Marcar mensagem como falha
     */
    public function markAsFailed(): bool
    {
        return $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Scope para buscar mensagens entre dois usuários
     */
    public function scopeBetweenUsers($query, string $userId1, string $userId2)
    {
        return $query->where(function ($q) use ($userId1, $userId2) {
            $q->where('sender_id', $userId1)
              ->where('receiver_id', $userId2);
        })->orWhere(function ($q) use ($userId1, $userId2) {
            $q->where('sender_id', $userId2)
              ->where('receiver_id', $userId1);
        });
    }

    /**
     * Scope para buscar mensagens não lidas de um usuário
     */
    public function scopeUnreadFor($query, string $userId)
    {
        return $query->where('receiver_id', $userId)
                    ->whereNull('read_at');
    }
}
