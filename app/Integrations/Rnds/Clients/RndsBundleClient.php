<?php

namespace App\Integrations\Rnds\Clients;

use App\Integrations\DTOs\FhirBundleDto;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class RndsBundleClient
{
    public function submitBundle(FhirBundleDto $bundle, string $accessToken): Response
    {
        $baseUrl = config('integrations.rnds.base_url');
        $cnes = config('integrations.rnds.cnes');
        $timeouts = config('integrations.timeouts.rnds', ['connect' => 10, 'response' => 60]);

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new \RuntimeException('RNDS: base_url não configurada.');
        }

        $headers = [
            'Accept' => 'application/fhir+json',
            'Content-Type' => 'application/fhir+json',
        ];
        if ($cnes) {
            $headers['X-Authorization-Server'] = "CNES/{$cnes}";
        }

        return Http::withToken($accessToken)
            ->withHeaders($headers)
            ->timeout($timeouts['response'])
            ->connectTimeout($timeouts['connect'])
            ->post(rtrim($baseUrl, '/').'/Bundle', $bundle->toFhirJson())
            ->throw();
    }
}
