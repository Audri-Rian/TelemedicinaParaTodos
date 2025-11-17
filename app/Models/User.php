<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
        'timeline_completed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'timeline_completed' => 'boolean',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Get the doctor profile associated with the user.
     */
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * Get the patient profile associated with the user.
     */
    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Get the timeline events associated with the user.
     */
    public function timelineEvents()
    {
        return $this->hasMany(TimelineEvent::class);
    }

    /**
     * Check if the user is a doctor.
     */
    public function isDoctor(): bool
    {
        return $this->doctor()->exists();
    }

    /**
     * Check if the user is a patient.
     */
    public function isPatient(): bool
    {
        return $this->patient()->exists();
    }

    /**
     * Get the user's role.
     */
    public function getRole(): string
    {
        if ($this->isDoctor()) {
            return 'doctor';
        }
        
        if ($this->isPatient()) {
            return 'patient';
        }
        
        return 'user';
    }

    /**
     * Get the avatar URL.
     *
     * @param bool $thumbnail Se deve retornar thumbnail
     * @return string|null
     */
    public function getAvatarUrl(bool $thumbnail = false): ?string
    {
        if (!$this->avatar_path) {
            return null;
        }

        return app(\App\Services\AvatarService::class)->getAvatarUrl($this->avatar_path, $thumbnail);
    }

    /**
     * Check if user has an avatar.
     *
     * @return bool
     */
    public function hasAvatar(): bool
    {
        return !empty($this->avatar_path);
    }
}