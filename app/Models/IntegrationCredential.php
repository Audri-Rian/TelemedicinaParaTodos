<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationCredential extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'partner_integration_id',
        'auth_type',
        'client_id',
        'client_secret',
        'access_token',
        'refresh_token',
        'certificate_path',
        'certificate_password',
        'scopes',
        'token_expires_at',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'certificate_password' => 'encrypted',
        'scopes' => 'array',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'client_secret',
        'access_token',
        'refresh_token',
        'certificate_password',
    ];

    // Constantes de tipo de autenticação
    public const AUTH_API_KEY = 'api_key';
    public const AUTH_OAUTH2_CLIENT_CREDENTIALS = 'oauth2_client_credentials';
    public const AUTH_OAUTH2_AUTHORIZATION_CODE = 'oauth2_authorization_code';
    public const AUTH_CERTIFICATE = 'certificate';
    public const AUTH_BASIC = 'basic_auth';
    public const AUTH_BEARER = 'bearer_token';

    // Relacionamentos

    public function partnerIntegration(): BelongsTo
    {
        return $this->belongsTo(PartnerIntegration::class);
    }

    // Métodos

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function isTokenExpiringSoon(int $minutes = 5): bool
    {
        return $this->token_expires_at
            && $this->token_expires_at->isBefore(now()->addMinutes($minutes));
    }
}
