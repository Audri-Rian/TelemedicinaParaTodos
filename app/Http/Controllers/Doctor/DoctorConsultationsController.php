<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class DoctorConsultationsController extends Controller
{
    public function index(): Response
    {
        // Buscar pacientes que têm consultas com este médico
        $doctor = Auth::user()->doctor;
        
        // Por enquanto, buscar todos os pacientes (pode ser filtrado por agendamentos futuros)
        $patients = User::whereHas('patient')->get(['id', 'name', 'email']);
        
        return Inertia::render('Consultations', [
            'users' => $patients
        ]);
    }
}


