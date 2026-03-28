<?php

namespace App\Integrations\Adapters;

use App\Models\IntegrationCredential;
use App\Models\PartnerIntegration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter base com lógica comum a todos os adapters de integração.
 *
 * Fornece HTTP client configurado, logging e tratamento de credenciais.
 */
abstract class BaseAdapter
{
    /**
     * Cria um HTTP client configurado para o parceiro.
     */
    protected function httpClient(PartnerIntegration $partner): PendingRequest
    {
        $timeouts = config("integrations.timeouts.{$partner->type}", [
            'connect' => 5,
            'response' => 15,
        ]);

        $request = Http::baseUrl($partner->base_url)
            ->timeout($timeouts['response'])
            ->connectTimeout($timeouts['connect'])
            ->withHeaders($this->getDefaultHeaders());

        return $this->applyAuthentication($request, $partner);
    }

    /**
     * Retorna os headers padrão para requisições FHIR.
     *
     * Subclasses podem sobrescrever para customizar headers.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/fhir+json',
            'Content-Type' => 'application/fhir+json; charset=utf-8',
        ];
    }

    /**
     * Aplica autenticação conforme tipo de credencial do parceiro.
     */
    protected function applyAuthentication(PendingRequest $request, PartnerIntegration $partner): PendingRequest
    {
        $partner->loadMissing('credential');
        $credential = $partner->credential;

        if (! $credential) {
            return $request;
        }

        return match ($credential->auth_type) {
            IntegrationCredential::AUTH_BEARER => $request->withToken($credential->access_token),
            IntegrationCredential::AUTH_API_KEY => $request->withHeaders(['X-API-Key' => $credential->client_id]),
            IntegrationCredential::AUTH_BASIC => $request->withBasicAuth($credential->client_id, $credential->client_secret),
            IntegrationCredential::AUTH_OAUTH2_CLIENT_CREDENTIALS => $request->withToken($this->getOAuthToken($credential)),
            default => $request,
        };
    }

    /**
     * Obtém token OAuth2 (usa cache ou renova se expirado).
     */
    protected function getOAuthToken(IntegrationCredential $credential): string
    {
        if (! $credential->isTokenExpiringSoon()) {
            return $credential->access_token;
        }

        // Token expirado ou expirando — renovar
        Log::channel('integration')->info('Renovando token OAuth2', [
            'partner_id' => $credential->partner_integration_id,
        ]);

        // TODO: Implementar renovação real no IntegrationService (passo 3)
        throw new \RuntimeException(
            "OAuth2 token expired or expiring soon for partner_integration_id={$credential->partner_integration_id}"
        );
    }

    /**
     * Log estruturado para operações de integração.
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        Log::channel('integration')->log($level, $message, $context);
    }
}
