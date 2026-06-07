<?php

namespace App\Integrations\Rnds\Token;

use App\Integrations\Rnds\Certificate\RndsCertificateManager;
use App\Integrations\Rnds\DTOs\RndsOAuthTokenDto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RndsTokenManager
{
    private function cacheKey(): string
    {
        return sprintf(
            'integrations:rnds:%s:%s:oauth_access_token',
            config('app.env'),
            config('integrations.rnds.cnes', 'default'),
        );
    }

    public function __construct(
        private readonly RndsCertificateManager $certificates,
    ) {}

    public function getAccessToken(): string
    {
        $cachedToken = Cache::get($this->cacheKey());
        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $authUrl = config('integrations.rnds.auth_url');
        if (! $this->certificates->isConfigured() || ! is_string($authUrl) || $authUrl === '') {
            throw new \RuntimeException(
                'RNDS: certificado e-CNPJ ou URL de autenticação não configurados.'
            );
        }

        $timeouts = config('integrations.timeouts.rnds', ['connect' => 10, 'response' => 60]);

        $response = Http::withOptions($this->certificates->mtlsHttpOptions())
            ->timeout($timeouts['response'])
            ->connectTimeout($timeouts['connect'])
            ->asForm()
            ->post(rtrim($authUrl, '/').'/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            Log::channel('integration')->warning('RNDS auth falhou', [
                'http_status' => $response->status(),
            ]);

            throw new \RuntimeException(
                "RNDS auth falhou: HTTP {$response->status()}",
                $response->status(),
            );
        }

        $dto = RndsOAuthTokenDto::fromOAuthJson($response->json() ?? []);
        Cache::put($this->cacheKey(), $dto->accessToken, now()->addSeconds($dto->expiresInSeconds));

        return $dto->accessToken;
    }
}
