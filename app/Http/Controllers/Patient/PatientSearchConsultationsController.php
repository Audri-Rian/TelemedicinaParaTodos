<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointments;
use App\Models\Doctor;

class PatientSearchConsultationsController extends Controller
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

        // Buscar especializações recomendadas (priorizar as com médicos disponíveis)
        $specializationsWithDoctors = \App\Models\Specialization::withCount(['doctors' => function ($query) {
                $query->where('status', 'active');
            }])
            ->whereHas('doctors', function ($query) {
                $query->where('status', 'active');
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        // Se não houver 6 especializações com médicos, completar com outras
        if ($specializationsWithDoctors->count() < 6) {
            $additionalSpecializations = \App\Models\Specialization::withCount(['doctors' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->whereNotIn('id', $specializationsWithDoctors->pluck('id'))
                ->orderBy('name')
                ->limit(6 - $specializationsWithDoctors->count())
                ->get();

            $specializations = $specializationsWithDoctors->merge($additionalSpecializations)->take(6);
        } else {
            $specializations = $specializationsWithDoctors->take(6);
        }

        return Inertia::render('Patient/SearchConsultations', [
            'appointments' => $appointments,
            'availableDoctors' => $availableDoctors,
            'specializations' => $specializations,
        ]);
    }
}

