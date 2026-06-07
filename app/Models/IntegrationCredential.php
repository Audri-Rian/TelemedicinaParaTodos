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
        'client_id' => 'encrypted',
        'client_secret' => 'encrypted',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'certificate_password' => 'encrypted',
        'scopes' => 'array',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'client_id',
        'client_id_hash',
        'client_secret',
        'access_token',
        'access_token_hash',
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

    protected static function booted(): void
    {
        static::saving(function (self $credential) {
            // client_id e access_token têm cast 'encrypted' — o valor em $credential->client_id
            // já está decriptado pelo Eloquent. Usamos ele diretamente para o hash.
            if ($credential->isDirty('client_id')) {
                $credential->client_id_hash = $credential->client_id !== null
                    ? hash('sha256', $credential->client_id)
                    : null;
            }

            if ($credential->isDirty('access_token')) {
                $credential->access_token_hash = $credential->access_token !== null
                    ? hash('sha256', $credential->access_token)
                    : null;
            }
        });
    }

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
