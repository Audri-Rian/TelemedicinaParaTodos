<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function createFromSystem(array $attributes): static
    {
        return static::forceCreate($attributes);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }
}
