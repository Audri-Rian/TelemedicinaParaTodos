<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, SocialAuthService $service): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google OAuth callback error', ['message' => $e->getMessage(), 'class' => get_class($e)]);

            return redirect()->route('login')->with('status', 'Login com Google cancelado ou falhou. Tente novamente.');
        }

        $intent = $request->session()->pull('google_oauth.intent');

        // Fluxo de vinculação (autenticado em Settings)
        if ($intent === 'link' && Auth::check()) {
            try {
                $service->linkGoogle(Auth::user(), $googleUser, $request->ip(), $request->userAgent() ?? '');
            } catch (\RuntimeException $e) {
                return redirect()->route('connected-accounts.show')->with('error', $e->getMessage());
            }

            return redirect()->route('connected-accounts.show')->with('status', 'Conta Google vinculada com sucesso.');
        }

        $result = $service->handleGoogleCallback($googleUser, $request->ip(), $request->userAgent() ?? '');

        return match ($result['result']) {
            SocialAuthService::RESULT_EMAIL_NOT_VERIFIED => redirect()->route('login')
                ->with('status', 'Sua conta Google não está verificada. Verifique seu e-mail no Google e tente novamente.'),

            SocialAuthService::RESULT_DOCTOR_BLOCKED => redirect()->route('login')
                ->with('status', 'Médicos devem entrar com e-mail e senha. O login social não está disponível para profissionais de saúde.'),

            SocialAuthService::RESULT_EMAIL_EXISTS => redirect()->route('login')
                ->with('status', 'Este e-mail já possui conta. Entre com e-mail e senha e conecte o Google em Configurações > Contas conectadas.'),

            SocialAuthService::RESULT_TWO_FACTOR => $this->redirectToTwoFactor($request, $result['user']),

            SocialAuthService::RESULT_COMPLETE_PROFILE => $this->loginAndRedirectToComplete($request, $result['user']),

            default => $this->loginAndRedirect($request, $result['user']),
        };
    }

    private function redirectToTwoFactor(Request $request, $user): RedirectResponse
    {
        $request->session()->put([
            'two_factor.user_id' => $user->id,
            'two_factor.remember' => false,
            'two_factor.pending_at' => now()->timestamp,
        ]);

        return redirect()->route('two-factor.challenge');
    }

    private function loginAndRedirectToComplete(Request $request, $user): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('register.patient.complete');
    }

    private function loginAndRedirect(Request $request, $user): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();

        if ($user->isDoctor()) {
            return redirect()->intended(route('doctor.dashboard', absolute: false));
        }

        if ($user->isPatient()) {
            return redirect()->intended(route('patient.dashboard', absolute: false));
        }

        return redirect()->intended(route('register.patient.complete'));
    }
}
