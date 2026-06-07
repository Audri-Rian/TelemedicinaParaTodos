<?php

namespace App\Integrations\Http\Middleware;

use App\Models\PartnerIntegration;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limiting por tipo de parceiro.
 *
 * Usa os limites definidos em config/integrations.php → rate_limits.
 */
class RateLimitPartner
{
    public function __construct(
        private readonly RateLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var PartnerIntegration|null $partner */
        $partner = $request->attributes->get('partner');

        if (! $partner) {
            return $next($request);
        }

        $perMinute = config("integrations.rate_limits.{$partner->type}.per_minute", 60);
        $key = "partner:{$partner->id}:api";

        if ($this->limiter->tooManyAttempts($key, $perMinute)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'error' => 'rate_limit_exceeded',
                'error_description' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $perMinute,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($key, 60);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $perMinute,
            'X-RateLimit-Remaining' => max(0, $perMinute - $this->limiter->attempts($key)),
        ]);
    }
}
