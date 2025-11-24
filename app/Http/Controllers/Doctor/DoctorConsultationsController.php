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

        // Primeiro, buscar apenas os IDs de pacientes que têm consultas com este médico
        $patientIdsWithAppointments = Appointments::where('doctor_id', $doctor->id)
            ->where('status', '!=', Appointments::STATUS_CANCELLED)
            ->distinct()
            ->pluck('patient_id')
            ->toArray();

        // Se não houver pacientes com consultas, retornar vazio
        if (empty($patientIdsWithAppointments)) {
            return Inertia::render('Doctor/Consultations', [
                'users' => [],
            ]);
        }

        // Buscar apenas os pacientes que têm relacionamento com o médico
        $patients = Patient::with('user')
            ->whereIn('id', $patientIdsWithAppointments)
            ->active()
            ->get();

        // Buscar todas as consultas desses pacientes com este médico
        $appointments = Appointments::where('doctor_id', $doctor->id)
            ->whereIn('patient_id', $patientIdsWithAppointments)
            ->where('status', '!=', Appointments::STATUS_CANCELLED)
            ->orderBy('scheduled_at', 'asc')
            ->get()
            ->groupBy('patient_id');

        $leadMinutes = (int) config('telemedicine.appointment.lead_minutes', 10);
        $now = Carbon::now();

        $users = $patients->map(function (Patient $patient) use ($appointments, $now, $leadMinutes) {
            $relatedAppointments = $appointments->get($patient->id, collect());

            // Priorizar consulta em andamento
            $primaryAppointment = $relatedAppointments->first(function (Appointments $appointment) {
                return $appointment->status === Appointments::STATUS_IN_PROGRESS;
            });

            // Se não há consulta em andamento, buscar próxima na janela de tempo
            if (!$primaryAppointment) {
                $primaryAppointment = $relatedAppointments->first(function (Appointments $appointment) use ($now, $leadMinutes) {
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

            // Se não há consulta na janela, buscar próxima futura
            if (!$primaryAppointment) {
                $primaryAppointment = $relatedAppointments->first(function (Appointments $appointment) use ($now) {
                    return in_array($appointment->status, [
                        Appointments::STATUS_SCHEDULED,
                        Appointments::STATUS_RESCHEDULED,
                    ]) && $appointment->scheduled_at->greaterThan($now);
                });
            }

            // Se não há consulta futura, buscar última passada
            if (!$primaryAppointment) {
                $primaryAppointment = $relatedAppointments->sortByDesc('scheduled_at')->first(function (Appointments $appointment) use ($now) {
                    return in_array($appointment->status, [
                        Appointments::STATUS_COMPLETED,
                        Appointments::STATUS_NO_SHOW,
                    ]) && $appointment->scheduled_at->lessThan($now);
                });
            }

            $canStartCall = false;
            $timeWindowMessage = __('Sem agendamento');

            if ($primaryAppointment) {
                $timeWindowMessage = null;

                if ($primaryAppointment->status === Appointments::STATUS_IN_PROGRESS) {
                    $canStartCall = true;
                    $timeWindowMessage = __('Consulta em andamento');
                } elseif (in_array($primaryAppointment->status, [
                    Appointments::STATUS_SCHEDULED,
                    Appointments::STATUS_RESCHEDULED,
                ])) {
                    $startWindow = $primaryAppointment->scheduled_at->copy()->subMinutes($leadMinutes);
                    $endWindow = $primaryAppointment->scheduled_at->copy()->addMinutes($leadMinutes);

                    if ($now->between($startWindow, $endWindow)) {
                        $canStartCall = true;
                        $diffMinutes = $primaryAppointment->scheduled_at->diffInMinutes($now, false);

                        if ($diffMinutes < 0) {
                            $timeWindowMessage = abs($diffMinutes) . ' min';
                        } elseif ($diffMinutes === 0) {
                            $timeWindowMessage = 'Agora';
                        } else {
                            $timeWindowMessage = 'Em ' . $diffMinutes . ' min';
                        }
                    } elseif ($primaryAppointment->scheduled_at->lessThan($startWindow)) {
                        $timeWindowMessage = 'Expirado';
                    } else {
                        $daysUntil = (int) $now->diffInDays($primaryAppointment->scheduled_at, false);
                        if ($daysUntil > 0) {
                            $timeWindowMessage = $daysUntil . ($daysUntil === 1 ? ' dia' : ' dias');
                        } else {
                            $hoursUntil = (int) $now->diffInHours($primaryAppointment->scheduled_at, false);
                            if ($hoursUntil > 0) {
                                $timeWindowMessage = 'Em ' . $hoursUntil . ($hoursUntil === 1 ? 'h' : 'h');
                            } else {
                                $minutesUntil = $now->diffInMinutes($primaryAppointment->scheduled_at, false);
                                $timeWindowMessage = 'Em ' . $minutesUntil . ' min';
                            }
                        }
                    }
                } elseif ($primaryAppointment->status === Appointments::STATUS_COMPLETED) {
                    $timeWindowMessage = __('Consulta finalizada');
                } elseif ($primaryAppointment->status === Appointments::STATUS_NO_SHOW) {
                    $timeWindowMessage = __('Consulta não comparecida');
                }
            }

            // Incluir todas as consultas para permitir seleção específica
            $allAppointments = $relatedAppointments->map(function (Appointments $appointment) {
                return [
                    'id' => $appointment->id,
                    'scheduled_at' => $appointment->scheduled_at->format('Y-m-d H:i:s'),
                    'formatted_date' => $appointment->scheduled_at->format('d/m/Y'),
                    'formatted_time' => $appointment->scheduled_at->format('H:i'),
                    'status' => $appointment->status,
                ];
            })->sortByDesc('scheduled_at')->values()->toArray();

            // Só retornar pacientes que têm pelo menos uma consulta
            if ($relatedAppointments->isEmpty()) {
                return null;
            }

            return [
                'id' => $patient->user->id,
                'name' => $patient->user->name,
                'email' => $patient->user->email,
                'hasAppointment' => $relatedAppointments->isNotEmpty(),
                'canStartCall' => $canStartCall,
                'appointment' => $primaryAppointment ? [
                    'id' => $primaryAppointment->id,
                    'scheduled_at' => $primaryAppointment->scheduled_at->format('Y-m-d H:i:s'),
                    'formatted_date' => $primaryAppointment->scheduled_at->format('d/m/Y'),
                    'formatted_time' => $primaryAppointment->scheduled_at->format('H:i'),
                    'status' => $primaryAppointment->status,
                ] : null,
                'allAppointments' => $allAppointments,
                'timeWindowMessage' => $timeWindowMessage,
            ];
        })
        ->filter(fn ($user) => $user !== null) // Remover pacientes sem consultas
        ->values();

        return Inertia::render('Doctor/Consultations', [
            'users' => $users,
        ]);
    }
}

