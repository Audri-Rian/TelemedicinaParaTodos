<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow public routes (register, login, and specializations) to pass through
        $publicRoutes = ['api/register', 'api/login', 'api/specializations'];
        $currentRoute = $request->route()->getName() ?? $request->path();
        
        foreach ($publicRoutes as $publicRoute) {
            if (str_contains($currentRoute, $publicRoute)) {
                return $next($request);
            }
        }

        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Check if user is active (only if status field exists)
        if (isset($user->status) && $user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active'
            ], 403);
        }

        // Update token last_used_at
        $token = $user->currentAccessToken();
        if ($token) {
            $token->update(['last_used_at' => now()]);
        }

        return $next($request);
    }
}