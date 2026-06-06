<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ConfirmTwoFactorRequest;
use App\Models\AuditLog;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();

        return Inertia::render('settings/TwoFactor', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'hasPassword' => $user->hasPassword(),
        ]);
    }

    public function enable(Request $request, TwoFactorAuthService $service): Response
    {
        $user = Auth::user();

        $secret = $service->generateSecret($user);
        $qrSvg = $service->getQrCodeSvg($user);

        return Inertia::render('settings/TwoFactor', [
            'enabled' => false,
            'hasPassword' => $user->hasPassword(),
            'qrSvg' => $qrSvg,
            'setupKey' => $secret,
        ]);
    }

    public function confirm(ConfirmTwoFactorRequest $request, TwoFactorAuthService $service): RedirectResponse
    {
        $user = Auth::user();

        $result = $service->confirmActivation($user, $request->validated('code'));

        if (! $result['confirmed']) {
            return back()->withErrors(['code' => 'Código inválido. Verifique o aplicativo autenticador.']);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => '2fa_enabled',
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('two-factor.show')
            ->with('recoveryCodes', $result['recovery_codes'])
            ->with('status', 'Verificação em dois fatores ativada com sucesso.');
    }

    public function destroy(Request $request, TwoFactorAuthService $service): RedirectResponse
    {
        $service->disable(Auth::user(), $request->ip(), $request->userAgent() ?? '');

        return redirect()->route('two-factor.show')
            ->with('status', 'Verificação em dois fatores desativada.');
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorAuthService $service): RedirectResponse
    {
        $codes = $service->regenerateRecoveryCodes(Auth::user());

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => '2fa_recovery_codes_regenerated',
            'resource_type' => 'user',
            'resource_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('two-factor.show')
            ->with('recoveryCodes', $codes);
    }
}
