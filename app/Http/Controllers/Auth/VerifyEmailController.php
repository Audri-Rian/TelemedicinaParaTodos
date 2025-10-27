<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user->hasVerifiedEmail()) {
            $route = $this->getDashboardRoute($user);
            return redirect()->intended($route.'?verified=1');
        }

        $request->fulfill();

        $route = $this->getDashboardRoute($user);
        return redirect()->intended($route.'?verified=1');
    }

    /**
     * Get the appropriate dashboard route based on user role.
     */
    private function getDashboardRoute($user): string
    {
        if ($user->isDoctor()) {
            return route('doctor.dashboard', absolute: false);
        }
        
        if ($user->isPatient()) {
            return route('patient.dashboard', absolute: false);
        }
        
        return route('home', absolute: false);
    }
}
