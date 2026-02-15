<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PatientConsultationDetailsController extends Controller
{
    public function __construct(
        private AppointmentService $appointmentService
    ) {
    }

    /**
     * Display the patient's consultation details page.
     */
    public function show(Request $request, Appointments $appointment): Response
    {
        $this->authorize('view', $appointment);

        $appointment->load(['doctor.user', 'doctor.specializations', 'patient.user', 'logs.user']);

        $patient = Auth::user()->patient;
        $formattedAppointment = [
            'id' => $appointment->id,
            'status' => $appointment->status,
            'scheduled_at' => optional($appointment->scheduled_at)->toIso8601String(),
            'started_at' => optional($appointment->started_at)->toIso8601String(),
            'ended_at' => optional($appointment->ended_at)->toIso8601String(),
            'access_code' => $appointment->access_code,
            'video_recording_url' => $appointment->video_recording_url,
            'notes' => $appointment->notes,
            'metadata' => $appointment->metadata,
            'doctor' => [
                'id' => $appointment->doctor->id,
                'crm' => $appointment->doctor->crm,
                'user' => [
                    'name' => $appointment->doctor->user->name,
                    'email' => $appointment->doctor->user->email,
                    'avatar' => $appointment->doctor->user->avatar ?? null,
                ],
                'specializations' => $appointment->doctor->specializations->map(fn ($specialization) => [
                    'id' => $specialization->id,
                    'name' => $specialization->name,
                ]),
            ],
            'patient' => [
                'id' => $appointment->patient->id,
                'user' => [
                    'name' => $appointment->patient->user->name,
                    'email' => $appointment->patient->user->email,
                ],
            ],
            'logs' => $appointment->logs
                ->sortBy('created_at')
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'event' => $log->event,
                    'payload' => $log->payload,
                    'created_at' => optional($log->created_at)->toIso8601String(),
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                    ] : null,
                ])
                ->values(),
        ];

        $formattedAppointment['can'] = [
            'start' => $this->appointmentService->canBeStarted($appointment),
            'cancel' => $this->appointmentService->canBeCancelled($appointment),
            'is_active' => $this->appointmentService->isActive($appointment),
            'is_upcoming' => $this->appointmentService->isUpcoming($appointment),
        ];

        return Inertia::render('Patient/ConsultationDetails', [
            'appointment' => $formattedAppointment,
        ]);
    }
}

