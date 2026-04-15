<?php

namespace App\Integrations\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica se o parceiro autenticado possui os scopes necessários.
 *
 * Uso: ->middleware('partner.scope:lab:read,lab:write')
 */
class CheckPartnerScope
{
    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        $partnerScopes = $request->attributes->get('partner_scopes', []);

        foreach ($requiredScopes as $scope) {
            if (! in_array($scope, $partnerScopes, true)) {
                return response()->json([
                    'error' => 'insufficient_scope',
                    'error_description' => "The request requires the '{$scope}' scope.",
                    'required_scope' => $scope,
                ], 403);
            }
        }

        return $next($request);
    }
}
