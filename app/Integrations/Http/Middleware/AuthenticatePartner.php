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
 * Valida o token, verifica se não expirou e injeta o parceiro no request.
 *
 * Nota: access_token tem cast 'encrypted' no model, então não é possível
 * fazer WHERE direto. Iteramos sobre credenciais com token ativo e
 * comparamos o hash SHA-256.
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

        // Buscar credenciais com token ativo (encrypted at rest, decryptado pelo Eloquent)
        $credential = IntegrationCredential::whereNotNull('access_token')
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '>', now())
            ->with('partnerIntegration')
            ->get()
            ->first(fn (IntegrationCredential $c) => hash_equals((string) $c->access_token, $hashedToken));

        if (! $credential) {
            Log::channel('integration')->warning('Invalid bearer token', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'The access token is invalid.',
            ], 401);
        }

        if ($credential->isTokenExpired()) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'The access token has expired.',
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
