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
            'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
        );

        // Remove informações sensíveis do servidor
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    /**
     * Constrói a política de Content Security Policy
     */
    private function buildCSP(): string
    {
        $isDevelopment = app()->environment('local', 'development');
        
        // Domínios do Vite para desenvolvimento
        // Nota: localhost resolve para IPv4 e IPv6, então não precisamos especificar [::1] separadamente
        $viteDevSources = $isDevelopment 
            ? ' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173'
            : '';

        // Conexões WebSocket do Reverb (porta 8090)
        $reverbWebSocketSources = $isDevelopment
            ? ' ws://127.0.0.1:8090 wss://127.0.0.1:8090 ws://localhost:8090 wss://localhost:8090'
            : '';

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com https://fonts.bunny.net https://cdn.jsdelivr.net{$viteDevSources}", // unsafe-inline/unsafe-eval necessário para Vite/Inertia
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://rsms.me{$viteDevSources}", // rsms.me para fonte Inter
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net https://rsms.me data:", // rsms.me para fonte Inter
            "img-src 'self' data: https: blob:{$viteDevSources}", // Permite imagens do Vite em desenvolvimento
            "connect-src 'self' https://api.peerjs.com https://cdn.jsdelivr.net https://unpkg.com wss://*.pusher.com ws://localhost:* http://localhost:*{$viteDevSources}{$reverbWebSocketSources}", // cdn.jsdelivr.net e unpkg.com para WASM do LottieFiles, Reverb WebSocket
            "media-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        // upgrade-insecure-requests apenas em produção
        if (!$isDevelopment) {
            $directives[] = "upgrade-insecure-requests";
        }

        return implode('; ', $directives);
    }
}

