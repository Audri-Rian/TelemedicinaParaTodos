<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Appointments;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PatientVideoCallController extends Controller
{
    /**
     * Display the patient's video call page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return Inertia::render('Patient/VideoCall', [
                'users' => [],
            ]);
        }

        // Buscar apenas médicos que têm ou tiveram appointments com este paciente
        // Inclui todos os status exceto cancelled (para mostrar histórico completo)
        $doctorIds = Appointments::where('patient_id', $patient->id)
            ->where('status', '!=', Appointments::STATUS_CANCELLED)
            ->distinct()
            ->pluck('doctor_id');

        // Buscar médicos relacionados com informações de agendamento
        $doctors = Doctor::with('user')
            ->whereIn('id', $doctorIds)
            ->active()
            ->get()
            ->map(function ($doctor) use ($patient) {
                $now = Carbon::now();
                
                // Buscar todos os agendamentos com este médico
                $allAppointments = Appointments::where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)
                    ->where('status', '!=', Appointments::STATUS_CANCELLED)
                    ->orderBy('scheduled_at', 'desc')
                    ->get();

                $primaryAppointment = null;
                $canStartCall = false;
                $timeWindowMessage = null;

                // Priorizar consulta em andamento
                $primaryAppointment = $allAppointments->first(function ($appointment) {
                    return $appointment->status === Appointments::STATUS_IN_PROGRESS;
                });

                // Se não há consulta em andamento, buscar próxima na janela de 10 minutos
                if (!$primaryAppointment) {
                    $primaryAppointment = $allAppointments->first(function ($appointment) use ($now) {
                        if (!in_array($appointment->status, [
                            Appointments::STATUS_SCHEDULED,
                            Appointments::STATUS_RESCHEDULED
                        ])) {
                            return false;
                        }
                        
                        $minutesDifference = (int) round(($appointment->scheduled_at->timestamp - $now->timestamp) / 60);
                        return $minutesDifference >= -10 && $minutesDifference <= 10;
                    });
                }

                // Se não há consulta na janela, buscar próxima futura
                if (!$primaryAppointment) {
                    $primaryAppointment = $allAppointments->first(function ($appointment) use ($now) {
                        return in_array($appointment->status, [
                            Appointments::STATUS_SCHEDULED,
                            Appointments::STATUS_RESCHEDULED
                        ]) && $appointment->scheduled_at->greaterThan($now);
                    });
                }

                // Se não há consulta futura, buscar última passada
                if (!$primaryAppointment) {
                    $primaryAppointment = $allAppointments->first(function ($appointment) use ($now) {
                        return in_array($appointment->status, [
                            Appointments::STATUS_COMPLETED,
                            Appointments::STATUS_NO_SHOW
                        ]) && $appointment->scheduled_at->lessThan($now);
                    });
                }

                if ($primaryAppointment) {
                    $scheduledAt = Carbon::parse($primaryAppointment->scheduled_at);
                    
                    // Calcular diferença em minutos (negativo = passou, positivo = futuro)
                    $minutesDifference = (int) round(($scheduledAt->timestamp - $now->timestamp) / 60);
                    
                    // Se a consulta está em progresso, sempre pode iniciar
                    if ($primaryAppointment->status === Appointments::STATUS_IN_PROGRESS) {
                        $canStartCall = true;
                        $timeWindowMessage = 'Consulta em andamento';
                    } 
                    // Verificar se está na janela de tempo permitida (10 minutos antes ou depois)
                    elseif ($minutesDifference >= -10 && $minutesDifference <= 10 && 
                            in_array($primaryAppointment->status, [
                                Appointments::STATUS_SCHEDULED,
                                Appointments::STATUS_RESCHEDULED
                            ])) {
                        $canStartCall = true;
                        if ($minutesDifference < 0) {
                            $timeWindowMessage = 'Tempo restante: ' . abs($minutesDifference) . ' min';
                        } elseif ($minutesDifference === 0) {
                            $timeWindowMessage = 'Horário da consulta';
                        } else {
                            $timeWindowMessage = 'Início em ' . $minutesDifference . ' min';
                        }
                    } else {
                        // Fora da janela de tempo ou consulta passada
                        if ($primaryAppointment->status === Appointments::STATUS_COMPLETED) {
                            $timeWindowMessage = 'Consulta finalizada';
                        } elseif ($primaryAppointment->status === Appointments::STATUS_NO_SHOW) {
                            $timeWindowMessage = 'Consulta não comparecida';
                        } elseif ($minutesDifference < -10) {
                            $timeWindowMessage = 'Janela de tempo expirada';
                        } else {
                            $daysUntil = (int) $now->diffInDays($scheduledAt, false);
                            if ($daysUntil > 0) {
                                $timeWindowMessage = 'Agendado para ' . $daysUntil . ($daysUntil === 1 ? ' dia' : ' dias');
                            } else {
                                $hoursUntil = (int) $now->diffInHours($scheduledAt, false);
                                $timeWindowMessage = 'Início em ' . $hoursUntil . ($hoursUntil === 1 ? ' hora' : ' horas');
                            }
                        }
                    }
                }

                // Preparar lista de todas as consultas para seleção
                $appointmentsList = $allAppointments->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'scheduled_at' => $appointment->scheduled_at->format('Y-m-d H:i:s'),
                        'formatted_date' => $appointment->scheduled_at->format('d/m/Y'),
                        'formatted_time' => $appointment->scheduled_at->format('H:i'),
                        'status' => $appointment->status,
                    ];
                })->toArray();

                return [
                    'id' => $doctor->user->id,
                    'name' => $doctor->user->name,
                    'email' => $doctor->user->email,
                    'hasAppointment' => $primaryAppointment !== null,
                    'canStartCall' => $canStartCall,
                    'appointment' => $primaryAppointment ? [
                        'id' => $primaryAppointment->id,
                        'scheduled_at' => $primaryAppointment->scheduled_at->format('Y-m-d H:i:s'),
                        'formatted_date' => $primaryAppointment->scheduled_at->format('d/m/Y'),
                        'formatted_time' => $primaryAppointment->scheduled_at->format('H:i'),
                        'status' => $primaryAppointment->status,
                    ] : null,
                    'allAppointments' => $appointmentsList,
                    'timeWindowMessage' => $timeWindowMessage,
                ];
            });

        return Inertia::render('Patient/VideoCall', [
            'users' => $doctors,
        ]);
    }
}

