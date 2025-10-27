<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
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

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
