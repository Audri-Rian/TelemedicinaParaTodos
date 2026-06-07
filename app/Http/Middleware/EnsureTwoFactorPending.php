<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorPending
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('two_factor.user_id');
        $pendingAt = $request->session()->get('two_factor.pending_at');

        if (! $userId || ! $pendingAt) {
            return redirect()->route('login');
        }

        // Expirado após 10 minutos
        if (now()->timestamp - $pendingAt > 600) {
            $request->session()->forget('two_factor');

            return redirect()->route('login');
        }

        return $next($request);
    }
}
