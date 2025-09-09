<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PatientRegistrationRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PatientRegistrationController extends Controller
{
    /**
     * Show the patient registration form.
     */
    public function create(): Response
    {
        return Inertia::render('auth/RegisterPatient');
    }

    /**
     * Handle patient registration request.
     */
    public function store(PatientRegistrationRequest $request) // ✅ Usar Request Class
    {
        $user = DB::transaction(function () use ($request) {
            // Criar o usuário
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Criar o paciente relacionado
            $user->patient()->create([
                // Obrigatórios (conforme migration)
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'phone_number' => $request->phone_number,
                'status' => 'active',

                // Opcionais (conforme migration)
                'emergency_contact' => $request->emergency_contact,
                'emergency_phone' => $request->emergency_phone,
                'medical_history' => $request->medical_history,
                'allergies' => $request->allergies,
                'current_medications' => $request->current_medications,
                'blood_type' => $request->blood_type,
                'height' => $request->height,
                'weight' => $request->weight,
                'insurance_provider' => $request->insurance_provider,
                'insurance_number' => $request->insurance_number,
                'consent_telemedicine' => (bool) $request->consent_telemedicine,
            ]);

            return $user;
        });

        Auth::login($user);
        return to_route('dashboard');
    }
}