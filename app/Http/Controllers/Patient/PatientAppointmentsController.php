<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointments;
use App\Models\Doctor;

class PatientAppointmentsController extends Controller
{
    public function index(): Response
    {
        $patient = Auth::user()->patient;

        // Buscar agendamentos do paciente
        $appointments = Appointments::with(['doctor.user', 'doctor.specializations'])
            ->byPatient($patient->id)
            ->orderBy('scheduled_at', 'desc')
            ->get();

        // Buscar médicos disponíveis para agendamento
        $availableDoctors = Doctor::with(['user', 'specializations'])
            ->active()
            ->get();

        return Inertia::render('Patient/Appointments', [
            'appointments' => $appointments,
            'availableDoctors' => $availableDoctors,
        ]);
    }
}



