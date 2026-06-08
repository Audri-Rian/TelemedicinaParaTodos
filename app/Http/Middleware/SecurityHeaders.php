<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para adicionar headers de segurança HTTP
 * Implementa CSP, HSTS, X-Frame-Options, etc.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy (CSP)
        $csp = $this->buildCSP();
        $response->headers->set('Content-Security-Policy', $csp);

        // Strict Transport Security (HSTS) - apenas em HTTPS
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // X-Frame-Options - previne clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // X-Content-Type-Options - previne MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-XSS-Protection (legacy, mas ainda útil)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy - controla informações de referrer
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy - controla features do navegador
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(self), microphone=(self), camera=(self), display-capture=(self), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
        );

        // Remove informações sensíveis do servidor
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    private static ?string $cachedCsp = null;

    /**
     * Constrói a política de Content Security Policy (cached por processo).
     */
    private function buildCSP(): string
    {
        return static::$cachedCsp ??= $this->computeCSP();
    }

    private function computeCSP(): string
    {
        $isDevelopment = app()->environment('local', 'development');

        $viteDevSources = $isDevelopment
            ? ' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173 ws://localhost:* http://localhost:*'
            : '';

        $reverbWebSocketSources = $isDevelopment
            ? ' ws://127.0.0.1:8090 wss://127.0.0.1:8090 ws://localhost:8090 wss://localhost:8090'
            : '';

        $reverbHost = (string) config('broadcasting.connections.reverb.options.host', '');
        $reverbHost = preg_replace('#^https?://#', '', $reverbHost);
        $reverbHost = preg_replace('#/.*$#', '', $reverbHost);
        $reverbPort = (string) config('broadcasting.connections.reverb.options.port', '');
        $reverbPublicSources = $reverbHost !== ''
            ? " ws://{$reverbHost} wss://{$reverbHost}".($reverbPort !== '' ? " ws://{$reverbHost}:{$reverbPort} wss://{$reverbHost}:{$reverbPort}" : '')
            : '';

        $sfuWebSocketSources = $this->sfuWebSocketSources();

        $unsafeEval = $isDevelopment ? " 'unsafe-eval'" : '';

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'{$unsafeEval} https://fonts.googleapis.com https://fonts.bunny.net https://cdn.jsdelivr.net https://static.cloudflareinsights.com{$viteDevSources}",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://rsms.me{$viteDevSources}",
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net https://rsms.me data:",
            "img-src 'self' data: https: blob:{$viteDevSources}",
            "connect-src 'self' https://api.peerjs.com https://cdn.jsdelivr.net https://unpkg.com wss://*.pusher.com{$viteDevSources}{$reverbWebSocketSources}{$reverbPublicSources}{$sfuWebSocketSources}",
            "media-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        if (! $isDevelopment) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    private function sfuWebSocketSources(): string
    {
        $sfuWsUrl = (string) config('services.media_gateway.sfu_ws_url', '');
        if ($sfuWsUrl === '') {
            return '';
        }

        $scheme = parse_url($sfuWsUrl, PHP_URL_SCHEME);
        $host = parse_url($sfuWsUrl, PHP_URL_HOST);
        $port = parse_url($sfuWsUrl, PHP_URL_PORT);

        if (! in_array($scheme, ['ws', 'wss'], true) || ! is_string($host) || $host === '') {
            return '';
        }

        return ' '.$scheme.'://'.$host.($port ? ':'.$port : '');
    }
}
