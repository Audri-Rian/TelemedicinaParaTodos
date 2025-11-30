<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

/**
 * Middleware para sanitizar inputs e prevenir XSS
 */
class SanitizeInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitizar apenas requisições POST, PUT, PATCH
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $input = $request->all();
            $sanitized = $this->sanitizeArray($input);
            
            // Substituir inputs sanitizados
            $request->merge($sanitized);
        }

        return $next($request);
    }

    /**
     * Sanitiza recursivamente um array
     */
    private function sanitizeArray(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // Campos que não devem ser sanitizados (senhas, tokens, etc.)
            $excludedKeys = ['password', 'password_confirmation', '_token', 'csrf_token', 'api_token'];
            
            if (in_array($key, $excludedKeys)) {
                $sanitized[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                // Remove tags HTML e entidades perigosas
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitiza uma string removendo XSS
     */
    private function sanitizeString(string $value): string
    {
        // Remove tags HTML perigosas, mas preserva formatação básica
        $value = strip_tags($value, '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6>');
        
        // Remove atributos perigosos de tags permitidas
        $value = preg_replace('/<(\w+)[^>]*>/i', '<$1>', $value);
        
        // Remove javascript: e data: URLs perigosas
        $value = preg_replace('/(javascript|data|vbscript):/i', '', $value);
        
        // Remove event handlers
        $value = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);
        
        // Remove caracteres de controle
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        return trim($value);
    }
}

