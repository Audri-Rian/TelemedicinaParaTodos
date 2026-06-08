<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompletePatientProfileRequest;
use App\Models\Consent;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CompletePatientProfileController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        $user = Auth::user();

        if ($user->isPatient()) {
            return redirect()->route('patient.dashboard');
        }

        $socialAccount = $user->socialAccounts()->where('provider', 'google')->first();

        return Inertia::render('auth/CompletePatientProfile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatarUrl' => $socialAccount?->avatar_url,
        ]);
    }

    public function store(CompletePatientProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        Patient::create([
            'user_id' => $user->id,
            'gender' => $data['gender'],
            'date_of_birth' => $data['date_of_birth'],
            'phone_number' => $data['phone_number'],
            'consent_telemedicine' => true,
        ]);

        Consent::create([
            'user_id' => $user->id,
            'type' => Consent::TYPE_TELEMEDICINE,
            'granted' => true,
            'granted_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('patient.dashboard');
    }
}
