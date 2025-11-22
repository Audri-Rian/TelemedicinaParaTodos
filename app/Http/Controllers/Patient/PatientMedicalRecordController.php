<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PatientMedicalRecordController extends Controller
{
    /**
     * Exibe a página de prontuário médico do paciente.
     */
    public function index(): Response
    {
        $patient = Auth::user()->patient;

        if (!$patient) {
            abort(403, 'Perfil de paciente não encontrado.');
        }

        // Buscar consultas do paciente (completadas)
        $appointments = Appointments::with(['doctor.user', 'doctor.specializations'])
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
                    'status' => $appointment->status,
                    'notes' => $appointment->notes,
                    'metadata' => $appointment->metadata ?? [],
                    'doctor' => [
                        'id' => $appointment->doctor->id,
                        'user' => [
                            'name' => $appointment->doctor->user->name,
                            'avatar' => $appointment->doctor->user->avatar ?? null,
                        ],
                        'specializations' => $appointment->doctor->specializations->map(fn ($spec) => [
                            'id' => $spec->id,
                            'name' => $spec->name,
                        ]),
                    ],
                    // Campos adicionais que podem estar no metadata
                    'diagnosis' => $appointment->metadata['diagnosis'] ?? null,
                    'cid10' => $appointment->metadata['cid10'] ?? null,
                    'symptoms' => $appointment->metadata['symptoms'] ?? null,
                    'requested_exams' => $appointment->metadata['requested_exams'] ?? null,
                    'instructions' => $appointment->metadata['instructions'] ?? null,
                    'attachments' => $appointment->metadata['attachments'] ?? [],
                    'prescriptions' => $appointment->metadata['prescriptions'] ?? [],
                ];
            });

        // Preparar dados do paciente
        $patientData = [
            'id' => $patient->id,
            'user' => [
                'name' => $patient->user->name,
                'avatar' => $patient->user->avatar ?? null,
            ],
            'date_of_birth' => $patient->date_of_birth ? $patient->date_of_birth->toIso8601String() : null,
            'gender' => $patient->gender,
            'age' => $patient->age,
        ];

        return Inertia::render('Patient/MedicalRecord', [
            'patient' => $patientData,
            'appointments' => $appointments,
        ]);
    }
}
