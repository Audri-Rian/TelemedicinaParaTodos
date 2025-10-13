<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DoctorRegistrationRequest;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Specialization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DoctorRegistrationController extends Controller
{
    /**
     * Show the doctor registration form.
     */
    public function create(): Response
    {
        $specializations = Specialization::orderBy('name')->get(['id', 'name']);
        
        return Inertia::render('auth/RegisterDoctor', [
            'specializations' => $specializations
        ]);
    }

    /**
     * Handle doctor registration request.
     */
    public function store(DoctorRegistrationRequest $request)
    {
        try {
            $user = DB::transaction(function () use ($request) {
                // Criar o usuário
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                // Criar o médico relacionado
                $doctor = $user->doctor()->create([
                    'crm' => $request->crm,
                    'status' => Doctor::STATUS_ACTIVE,
                ]);

                // Associar especialização(ões) ao médico
                if (!empty($request->specializations)) {
                    $doctor->specializations()->attach($request->specializations);
                }

                return $user;
            });

            Auth::login($user);
            
            return redirect()->route('doctor.dashboard')
                ->with('success', 'Conta de médico criada com sucesso! Bem-vindo à Telemedicina para Todos.');
                
        } catch (\Exception $e) {
            \Log::error('Erro no registro de médico: ' . $e->getMessage());
            
            return back()
                ->withErrors(['general' => 'Erro interno do servidor. Tente novamente em alguns instantes.'])
                ->withInput();
        }
    }
}
