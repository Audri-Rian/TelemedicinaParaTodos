<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('home');
        }

        if ($user->isDoctor()) {
            return redirect()->route('doctor.dashboard');
        }

        if ($user->isPatient()) {
            return redirect()->route('patient.dashboard');
        }

        return redirect()->route('home');
    }
}
