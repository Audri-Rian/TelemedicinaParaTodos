<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PatientHistoryConsultationsController extends Controller
{
    /**
     * Display the patient's history consultations page.
     */
    public function index(Request $request): Response
    {
        $patient = Auth::user()->patient;

        if (!$patient) {
            abort(403, 'Perfil de paciente não encontrado.');
        }

        $query = Appointments::query()
            ->with(['doctor.user', 'doctor.specializations'])
            ->where('patient_id', $patient->id)
            ->orderBy('scheduled_at', 'desc');

        $status = $request->get('status');

        if ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'completed') {
            $query->completed();
        } elseif ($status === 'cancelled') {
            $query->cancelled();
        } elseif ($status === 'rescheduled') {
            $query->where('status', Appointments::STATUS_RESCHEDULED);
        } elseif ($status === 'no_show') {
            $query->where('status', Appointments::STATUS_NO_SHOW);
        }

        $perPage = (int) config('telemedicine.pagination.consultations_per_page', 10);
        $appointments = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Appointments $appointment) {
                $doctor = $appointment->doctor;
                $doctorUser = $doctor->user ?? null;
                
                return [
                    'id' => $appointment->id,
                    'status' => $appointment->status,
                    'scheduled_at' => optional($appointment->scheduled_at)->toIso8601String(),
                    'doctor' => [
                        'id' => $doctor->id ?? '',
                        'user' => [
                            'id' => $doctorUser->id ?? '',
                            'name' => $doctorUser->name ?? 'Médico não informado',
                            'avatar' => $doctorUser->avatar ?? null,
                        ],
                        'specializations' => $doctor->specializations 
                            ? $doctor->specializations->map(fn ($spec) => [
                                'id' => $spec->id,
                                'name' => $spec->name,
                            ])->toArray()
                            : [],
                    ],
                ];
            });

        $stats = [
            'total' => Appointments::where('patient_id', $patient->id)->count(),
            'upcoming' => Appointments::where('patient_id', $patient->id)->upcoming()->count(),
            'completed' => Appointments::where('patient_id', $patient->id)->completed()->count(),
            'cancelled' => Appointments::where('patient_id', $patient->id)->cancelled()->count(),
        ];

        return Inertia::render('Patient/HistoryConsultations', [
            'appointments' => $appointments,
            'stats' => $stats,
            'filters' => [
                'status' => $status ?? 'all',
            ],
        ]);
    }
}

