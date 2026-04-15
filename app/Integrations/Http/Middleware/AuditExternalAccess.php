<?php

namespace App\Integrations\Http\Middleware;

use App\Models\PartnerIntegration;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra toda chamada de parceiro externo à API pública.
 *
 * Loga: parceiro, endpoint, método, IP, status, duração.
 */
class AuditExternalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        /** @var PartnerIntegration|null $partner */
        $partner = $request->attributes->get('partner');

        Log::channel('integration')->info('API externa acessada', [
            'partner_id' => $partner?->id,
            'partner_name' => $partner?->name,
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }
}
