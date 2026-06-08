<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response
    {
        $codes = $request->session()->get('two_factor.user_id')
            ? User::find($request->session()->get('two_factor.user_id'))?->two_factor_recovery_codes
            : null;

        return Inertia::render('auth/TwoFactorChallenge', [
            'recoveryAvailable' => ! empty($codes),
        ]);
    }

    public function store(TwoFactorChallengeRequest $request, TwoFactorAuthService $service): RedirectResponse
    {
        $userId = $request->session()->get('two_factor.user_id');
        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $service->verify($user, $request->validated('code'))) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => '2fa_challenge_failed',
                'resource_type' => 'user',
                'resource_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->withErrors(['code' => 'Código inválido. Verifique o aplicativo autenticador ou use um código de recuperação.']);
        }

        // Uso de recovery code: logar evento
        $isRecovery = ! (ctype_digit($request->validated('code')) && strlen($request->validated('code')) === 6);
        if ($isRecovery) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'recovery_code_used',
                'resource_type' => 'user',
                'resource_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => ['remaining_codes' => count($user->fresh()->two_factor_recovery_codes ?? [])],
            ]);
        }

        $remember = (bool) $request->session()->get('two_factor.remember', false);
        $request->session()->forget('two_factor');

        Auth::login($user, $remember);
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
