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
                $appointment = null;
                $canStartCall = false;
                $timeWindowMessage = null;

                // Buscar próximo agendamento com este médico (dentro de 10 minutos ou ainda não aconteceu)
                $appointment = Appointments::where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)
                    ->whereIn('status', [
                        Appointments::STATUS_SCHEDULED,
                        Appointments::STATUS_RESCHEDULED,
                        Appointments::STATUS_IN_PROGRESS
                    ])
                    ->where('scheduled_at', '>=', Carbon::now()->subMinutes(10))
                    ->where('scheduled_at', '<=', Carbon::now()->addMinutes(10))
                    ->orderBy('scheduled_at', 'asc')
                    ->first();
                
                // Se não encontrou na janela de 10 minutos, busca o próximo agendamento futuro
                if (!$appointment) {
                    $appointment = Appointments::where('doctor_id', $doctor->id)
                        ->where('patient_id', $patient->id)
                        ->whereIn('status', [
                            Appointments::STATUS_SCHEDULED,
                            Appointments::STATUS_RESCHEDULED
                        ])
                        ->where('scheduled_at', '>', Carbon::now())
                        ->orderBy('scheduled_at', 'asc')
                        ->first();
                }

                // Se ainda não encontrou, busca o último agendamento passado (para histórico)
                if (!$appointment) {
                    $appointment = Appointments::where('doctor_id', $doctor->id)
                        ->where('patient_id', $patient->id)
                        ->whereIn('status', [
                            Appointments::STATUS_COMPLETED,
                            Appointments::STATUS_NO_SHOW
                        ])
                        ->where('scheduled_at', '<', Carbon::now())
                        ->orderBy('scheduled_at', 'desc')
                        ->first();
                }

                if ($appointment) {
                    $now = Carbon::now();
                    $scheduledAt = Carbon::parse($appointment->scheduled_at);
                    
                    // Calcular diferença em minutos (negativo = passou, positivo = futuro)
                    $minutesDifference = (int) round(($scheduledAt->timestamp - $now->timestamp) / 60);
                    
                    // Se a consulta está em progresso, sempre pode iniciar
                    if ($appointment->status === Appointments::STATUS_IN_PROGRESS) {
                        $canStartCall = true;
                        $timeWindowMessage = 'Consulta em andamento';
                    } 
                    // Verificar se está na janela de tempo permitida (10 minutos antes ou depois)
                    elseif ($minutesDifference >= -10 && $minutesDifference <= 10 && 
                            in_array($appointment->status, [
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
                        if ($appointment->status === Appointments::STATUS_COMPLETED) {
                            $timeWindowMessage = 'Consulta finalizada';
                        } elseif ($appointment->status === Appointments::STATUS_NO_SHOW) {
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

                return [
                    'id' => $doctor->user->id,
                    'name' => $doctor->user->name,
                    'email' => $doctor->user->email,
                    'hasAppointment' => $appointment !== null,
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
            });

        return Inertia::render('Patient/VideoCall', [
            'users' => $doctors,
        ]);
    }
}

