<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        $this->rememberIntendedRedirectFromQuery($request);

        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirecionar baseado no tipo de usuário
        $user = Auth::user();

        if ($user->isDoctor()) {
            return redirect()->intended(route('doctor.dashboard', absolute: false));
        }

        if ($user->isPatient()) {
            return redirect()->intended(route('patient.dashboard', absolute: false));
        }

        // Fallback caso não tenha perfil definido
        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function rememberIntendedRedirectFromQuery(Request $request): void
    {
        $candidate = $request->query('redirect');
        if (! is_string($candidate) || $candidate === '') {
            return;
        }

        if (strlen($candidate) > 2048) {
            return;
        }

        if (! str_starts_with($candidate, '/') || str_starts_with($candidate, '//')) {
            return;
        }

        if (str_contains($candidate, '\\')) {
            return;
        }

        $request->session()->put('url.intended', $candidate);
    }
}
