<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Specialization extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'name' => 'string',
    ];

    // Relacionamento N:N com Doctor
    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_specialization')
                    ->withTimestamps();
    }

    // Scopes para filtros
    public function scopeByName(Builder $query, string $name): void
    {
        $query->where('name', 'like', '%' . $name . '%');
    }

    public function scopeOrderByName(Builder $query): void
    {
        $query->orderBy('name');
    }

    public function scopeWithDoctorCount(Builder $query): void
    {
        $query->withCount('doctors');
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereHas('doctors', function ($doctorQuery) {
            $doctorQuery->where('status', Doctor::STATUS_ACTIVE);
        });
    }

    // Accessors
    public function getFormattedNameAttribute(): string
    {
        return ucwords(strtolower($this->name));
    }

    // Mutators
    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = ucwords(strtolower(trim($value)));
    }

    // Métodos auxiliares
    public function getDoctorsCountAttribute(): int
    {
        return $this->doctors()->count();
    }

    public function getActiveDoctorsCountAttribute(): int
    {
        return $this->doctors()->where('status', Doctor::STATUS_ACTIVE)->count();
    }

    // Boot method para configurações automáticas
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($specialization) {
            // Garantir que o nome seja único (case-insensitive)
            $existingCount = static::where('name', $specialization->name)->count();
            if ($existingCount > 0) {
                throw new \InvalidArgumentException('Uma especialização com este nome já existe.');
            }
        });
    }

    // Get route key name
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
