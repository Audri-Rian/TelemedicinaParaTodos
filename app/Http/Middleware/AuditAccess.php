<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware para auditar acessos e ações
 */
class AuditAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Registrar apenas ações importantes e autenticadas
        if (Auth::check() && $this->shouldAudit($request)) {
            $this->logAccess($request, $response);
        }

        return $response;
    }

    /**
     * Verifica se a requisição deve ser auditada
     */
    private function shouldAudit(Request $request): bool
    {
        // Auditar apenas métodos que modificam ou acessam dados sensíveis
        $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        $sensitiveRoutes = [
            'consultations',
            'medical-record',
            'patients',
            'appointments',
            'prescriptions',
            'examinations',
        ];

        if (!in_array($request->method(), $methods)) {
            return false;
        }

        foreach ($sensitiveRoutes as $route) {
            if ($request->is("*{$route}*")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registra o acesso no log de auditoria
     */
    private function logAccess(Request $request, Response $response): void
    {
        try {
            $action = $this->determineAction($request);
            $resourceType = $this->determineResourceType($request);
            $resourceId = $this->determineResourceId($request);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_status' => $response->getStatusCode(),
                'metadata' => [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'route' => $request->route()?->getName(),
                ],
            ]);
        } catch (\Exception $e) {
            // Não quebrar a aplicação se o log falhar
            \Log::error('Erro ao registrar auditoria: ' . $e->getMessage());
        }
    }

    /**
     * Determina a ação baseada na requisição
     */
    private function determineAction(Request $request): string
    {
        $method = $request->method();
        $route = $request->route()?->getName() ?? '';

        if (str_contains($route, 'create') || str_contains($route, 'store')) {
            return 'create';
        }
        if (str_contains($route, 'update') || $method === 'PUT' || $method === 'PATCH') {
            return 'update';
        }
        if (str_contains($route, 'delete') || str_contains($route, 'destroy') || $method === 'DELETE') {
            return 'delete';
        }
        if (str_contains($route, 'export') || str_contains($route, 'download')) {
            return 'export';
        }
        if ($method === 'GET') {
            return 'view';
        }

        return strtolower($method);
    }

    /**
     * Determina o tipo de recurso
     */
    private function determineResourceType(Request $request): ?string
    {
        $route = $request->route()?->getName() ?? '';
        $path = $request->path();

        if (str_contains($route, 'consultation') || str_contains($path, 'consultation')) {
            return 'Consultation';
        }
        if (str_contains($route, 'medical-record') || str_contains($path, 'medical-record')) {
            return 'MedicalRecord';
        }
        if (str_contains($route, 'patient') || str_contains($path, 'patient')) {
            return 'Patient';
        }
        if (str_contains($route, 'appointment') || str_contains($path, 'appointment')) {
            return 'Appointment';
        }
        if (str_contains($route, 'prescription') || str_contains($path, 'prescription')) {
            return 'Prescription';
        }

        return null;
    }

    /**
     * Determina o ID do recurso
     */
    private function determineResourceId(Request $request): ?string
    {
        $route = $request->route();
        
        // Tentar pegar ID de parâmetros da rota
        $params = $route?->parameters();
        
        foreach (['appointment', 'patient', 'consultation', 'id'] as $key) {
            if (isset($params[$key])) {
                return (string) $params[$key];
            }
        }

        return null;
    }

    /**
     * Sanitiza dados da requisição para log (remove senhas, tokens, etc.)
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitive = ['password', 'password_confirmation', '_token', 'csrf_token', 'api_token'];
        
        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }
}

