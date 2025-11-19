<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Appointments;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DoctorConsultationsController extends Controller
{
    public function index(): Response
    {
        $currentUser = Auth::user()->loadMissing('doctor');

        if (!$currentUser->doctor) {
            return Inertia::render('Doctor/Consultations', [
                'users' => [],
            ]);
        }

        $doctor = $currentUser->doctor;

        $patients = Patient::with('user')->get();
        $patientIds = $patients->pluck('id');

        $appointments = Appointments::where('doctor_id', $doctor->id)
            ->whereIn('patient_id', $patientIds)
            ->where('status', '!=', Appointments::STATUS_CANCELLED)
            ->orderBy('scheduled_at', 'asc')
            ->get()
            ->groupBy('patient_id');

        $leadMinutes = (int) config('telemedicine.appointment.lead_minutes', 10);
        $now = Carbon::now();

        $users = $patients->map(function (Patient $patient) use ($appointments, $now, $leadMinutes) {
            $relatedAppointments = $appointments->get($patient->id, collect());

            $appointment = $relatedAppointments->first(function (Appointments $appointment) {
                return $appointment->status === Appointments::STATUS_IN_PROGRESS;
            });

            if (!$appointment) {
                $appointment = $relatedAppointments->first(function (Appointments $appointment) use ($now, $leadMinutes) {
                    if (!in_array($appointment->status, [
                        Appointments::STATUS_SCHEDULED,
                        Appointments::STATUS_RESCHEDULED,
                    ])) {
                        return false;
                    }

                    $startWindow = $appointment->scheduled_at->copy()->subMinutes($leadMinutes);
                    $endWindow = $appointment->scheduled_at->copy()->addMinutes($leadMinutes);

                    return $now->between($startWindow, $endWindow);
                });
            }

            if (!$appointment) {
                $appointment = $relatedAppointments->first(function (Appointments $appointment) use ($now) {
                    return in_array($appointment->status, [
                        Appointments::STATUS_SCHEDULED,
                        Appointments::STATUS_RESCHEDULED,
                    ]) && $appointment->scheduled_at->greaterThan($now);
                });
            }

            if (!$appointment) {
                $appointment = $relatedAppointments->first(function (Appointments $appointment) use ($now) {
                    return in_array($appointment->status, [
                        Appointments::STATUS_COMPLETED,
                        Appointments::STATUS_NO_SHOW,
                    ]) && $appointment->scheduled_at->lessThan($now);
                });
            }

            $canStartCall = false;
            $timeWindowMessage = __('Sem agendamento');

            if ($appointment) {
                $timeWindowMessage = null;

                if ($appointment->status === Appointments::STATUS_IN_PROGRESS) {
                    $canStartCall = true;
                    $timeWindowMessage = __('Consulta em andamento');
                } elseif (in_array($appointment->status, [
                    Appointments::STATUS_SCHEDULED,
                    Appointments::STATUS_RESCHEDULED,
                ])) {
                    $startWindow = $appointment->scheduled_at->copy()->subMinutes($leadMinutes);
                    $endWindow = $appointment->scheduled_at->copy()->addMinutes($leadMinutes);

                    if ($now->between($startWindow, $endWindow)) {
                        $canStartCall = true;
                        $diffMinutes = $appointment->scheduled_at->diffInMinutes($now, false);

                        if ($diffMinutes < 0) {
                            $timeWindowMessage = __('Tempo restante: :minutes min', [
                                'minutes' => abs($diffMinutes),
                            ]);
                        } elseif ($diffMinutes === 0) {
                            $timeWindowMessage = __('Horário da consulta');
                        } else {
                            $timeWindowMessage = __('Início em :minutes min', [
                                'minutes' => $diffMinutes,
                            ]);
                        }
                    } elseif ($appointment->scheduled_at->lessThan($startWindow)) {
                        $timeWindowMessage = __('Janela de tempo expirada');
                    } else {
                        $timeWindowMessage = __('Início em :minutes min', [
                            'minutes' => $now->diffInMinutes($appointment->scheduled_at, false),
                        ]);
                    }
                } elseif ($appointment->status === Appointments::STATUS_COMPLETED) {
                    $timeWindowMessage = __('Consulta finalizada');
                } elseif ($appointment->status === Appointments::STATUS_NO_SHOW) {
                    $timeWindowMessage = __('Consulta não comparecida');
                }
            }

            return [
                'id' => $patient->user->id,
                'name' => $patient->user->name,
                'email' => $patient->user->email,
                'hasAppointment' => $relatedAppointments->isNotEmpty(),
                'canStartCall' => $canStartCall,
                'appointment' => $appointment ? [
                    'id' => $appointment->id,
                    'scheduled_at' => $appointment->scheduled_at->format('Y-m-d H:i:s'),
                    'formatted_date' => $appointment->scheduled_at->format('d/m/Y'),
                    'formatted_time' => $appointment->scheduled_at->format('H:i'),
                    'status' => $appointment->status,
                ] : null,
                'timeWindowMessage' => $timeWindowMessage,
            ];
        })->values();

        return Inertia::render('Doctor/Consultations', [
            'users' => $users,
        ]);
    }
}

