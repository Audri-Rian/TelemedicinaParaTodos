<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the email verification prompt page.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $user = $request->user();
        
        if ($user->hasVerifiedEmail()) {
            // Redirect based on user role
            if ($user->isDoctor()) {
                return redirect()->intended(route('doctor.dashboard', absolute: false));
            }
            
            if ($user->isPatient()) {
                return redirect()->intended(route('patient.dashboard', absolute: false));
            }
        }
        
        return Inertia::render('auth/VerifyEmail', ['status' => $request->session()->get('status')]);
    }
}
