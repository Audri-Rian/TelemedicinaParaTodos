<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $fillable = [
        'call_id',
        'room_id',
        'sfu_node',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }
}
