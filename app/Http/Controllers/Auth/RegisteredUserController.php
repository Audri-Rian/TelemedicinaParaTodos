<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Redireciona para a página de registro de paciente.
     *
     * A rota genérica /register foi mantida para retrocompatibilidade
     * (links externos, redes sociais), mas o fluxo padrão hoje é o de
     * pacientes. Médicos devem usar /register/doctor diretamente.
     */
    public function create(): RedirectResponse
    {
        return to_route('register.patient');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Redirect based on user role
        if ($user->isDoctor()) {
            return to_route('doctor.dashboard');
        }
        
        if ($user->isPatient()) {
            return to_route('patient.dashboard');
        }
        
        return to_route('home');
    }
}
