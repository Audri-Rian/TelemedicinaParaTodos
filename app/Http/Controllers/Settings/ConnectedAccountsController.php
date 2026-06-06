<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class ConnectedAccountsController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();
        $googleAccount = $user->socialAccounts()->where('provider', 'google')->first();

        return Inertia::render('settings/ConnectedAccounts', [
            'googleLinked' => ! is_null($googleAccount),
            'googleEmail' => $googleAccount?->provider_email,
            'hasPassword' => $user->hasPassword(),
        ]);
    }

    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->hasPassword()) {
            return redirect()->route('connected-accounts.show')
                ->with('error', 'Você precisa criar uma senha antes de vincular o Google. Acesse Configurações > Senha.');
        }

        $request->session()->put('google_oauth.intent', 'link');

        return Socialite::driver('google')->redirect();
    }

    public function destroyGoogle(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->hasPassword()) {
            return redirect()->route('connected-accounts.show')
                ->with('error', 'Você precisa criar uma senha antes de desvincular o Google, para não perder acesso à conta.');
        }

        $deleted = $user->socialAccounts()->where('provider', 'google')->delete();

        if ($deleted) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'social_unlink',
                'resource_type' => 'user',
                'resource_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => ['provider' => 'google'],
            ]);
        }

        return redirect()->route('connected-accounts.show')
            ->with('status', 'Conta Google desvinculada.');
    }
}
