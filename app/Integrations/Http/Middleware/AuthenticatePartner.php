<?php

namespace App\Integrations\Http\Middleware;

use App\Models\IntegrationCredential;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica parceiro via Bearer token OAuth2.
 *
 * Lookup via access_token_hash (SHA-256 indexado) — O(1), sem scan de tabela.
 * Expiry verificado no WHERE; parceiro injetado em $request->attributes.
 */
class AuthenticatePartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'error' => 'unauthorized',
                'error_description' => 'Bearer token required.',
            ], 401);
        }

        $hashedToken = hash('sha256', $token);

        $credential = IntegrationCredential::where('access_token_hash', $hashedToken)
            ->where('auth_type', IntegrationCredential::AUTH_OAUTH2_CLIENT_CREDENTIALS)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '>', now())
            ->with('partnerIntegration')
            ->first();

        if (! $credential) {
            Log::channel('integration')->warning('Invalid bearer token', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'The access token is invalid.',
            ], 401);
        }

        $partner = $credential->partnerIntegration;

        if (! $partner || ! $partner->isActive()) {
            return response()->json([
                'error' => 'forbidden',
                'error_description' => 'Partner integration is not active.',
            ], 403);
        }

        $request->attributes->set('partner', $partner);
        $request->attributes->set('partner_scopes', $credential->scopes ?? []);

        return $next($request);
    }
}
