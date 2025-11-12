<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Doctor;
use App\Models\Appointments;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class PatientMessagesController extends Controller
{
    /**
     * Display the patient's messages page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $patient = Patient::where('user_id', $user->id)->first();

        $conversations = [];

        if ($patient) {
            // Buscar apenas médicos que têm ou tiveram appointments com este paciente
            // Inclui todos os status exceto cancelled (para mostrar histórico completo)
            $doctorIds = Appointments::where('patient_id', $patient->id)
                ->where('status', '!=', Appointments::STATUS_CANCELLED)
                ->distinct()
                ->pluck('doctor_id');

            // Buscar médicos relacionados
            $doctors = Doctor::with('user')
                ->whereIn('id', $doctorIds)
                ->active()
                ->get();

            $conversations = $doctors->map(function ($doctor) {
                return [
                    'id' => $doctor->user->id,
                    'doctorName' => $doctor->user->name,
                    'doctorEmail' => $doctor->user->email,
                    'doctorAvatar' => null, // Pode ser implementado depois com AvatarService
                ];
            })->toArray();
        }

        return Inertia::render('Patient/Messages', [
            'conversations' => $conversations,
        ]);
    }
}

