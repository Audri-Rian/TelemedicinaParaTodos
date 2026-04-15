<?php

namespace App\Integrations\Http\Controllers;

use App\Models\IntegrationCredential;
use App\Models\PartnerIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OAuth2 Client Credentials — emite tokens para parceiros externos.
 *
 * POST /api/v1/public/oauth/token
 *
 * Parceiros enviam client_id + client_secret e recebem um access_token
 * com TTL e scopes baseados no tipo de parceiro.
 */
class OAuthTokenController
{
    public function issueToken(Request $request): JsonResponse
    {
        $request->validate([
            'grant_type' => ['required', 'string', 'in:client_credentials'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'scope' => ['nullable', 'string'],
        ]);

        $credential = IntegrationCredential::where('client_id', $request->input('client_id'))
            ->where('auth_type', IntegrationCredential::AUTH_OAUTH2_CLIENT_CREDENTIALS)
            ->with('partnerIntegration')
            ->first();

        // client_secret tem cast 'encrypted' no model — Eloquent decripta automaticamente.
        // Comparação timing-safe com o valor decriptado (já protegido at rest).
        if (! $credential || ! hash_equals((string) $credential->client_secret, $request->input('client_secret'))) {
            Log::channel('integration')->warning('OAuth2 token request failed: invalid credentials', [
                'client_id' => $request->input('client_id'),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
            ], 401);
        }

        $partner = $credential->partnerIntegration;

        if (! $partner || ! $partner->isActive()) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Partner integration is not active.',
            ], 403);
        }

        // Determinar scopes permitidos baseado no tipo de parceiro
        $requestedScopes = $request->input('scope')
            ? explode(' ', $request->input('scope'))
            : [];

        $allowedScopes = $this->getAllowedScopes($partner);

        // Validar scopes requisitados (RFC 6749 — invalid_scope)
        if (! empty($requestedScopes)) {
            $invalidScopes = array_diff($requestedScopes, $allowedScopes);
            if (! empty($invalidScopes)) {
                return response()->json([
                    'error' => 'invalid_scope',
                    'error_description' => 'The requested scope is invalid: ' . implode(', ', $invalidScopes),
                ], 400);
            }
            $grantedScopes = $requestedScopes;
        } else {
            $grantedScopes = $allowedScopes;
        }

        // Gerar token criptograficamente seguro
        $token = bin2hex(random_bytes(32));
        $expiresIn = 3600; // 1 hora

        $credential->update([
            'access_token' => hash('sha256', $token),
            'token_expires_at' => now()->addSeconds($expiresIn),
            'scopes' => array_values($grantedScopes),
        ]);

        Log::channel('integration')->info('OAuth2 token issued', [
            'partner_id' => $partner->id,
            'scopes' => $grantedScopes,
            'expires_in' => $expiresIn,
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'scope' => implode(' ', $grantedScopes),
        ]);
    }

    private function getAllowedScopes(PartnerIntegration $partner): array
    {
        return match ($partner->type) {
            PartnerIntegration::TYPE_LABORATORY => ['lab:read', 'lab:write', 'webhook:send'],
            PartnerIntegration::TYPE_PHARMACY => ['pharmacy:read', 'pharmacy:write'],
            PartnerIntegration::TYPE_HOSPITAL => ['hospital:read', 'hospital:write'],
            PartnerIntegration::TYPE_INSURANCE => ['insurance:read', 'insurance:write'],
            default => [],
        };
    }
}
