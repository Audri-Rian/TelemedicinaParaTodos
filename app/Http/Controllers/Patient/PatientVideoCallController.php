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

        // Buscar médicos disponíveis com informações de agendamento
        $doctors = Doctor::with('user')
            ->active()
            ->get()
            ->map(function ($doctor) use ($patient) {
                $appointment = null;
                $canStartCall = false;
                $timeWindowMessage = null;

                if ($patient) {
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
                        elseif ($minutesDifference >= -10 && $minutesDifference <= 10) {
                            $canStartCall = true;
                            if ($minutesDifference < 0) {
                                $timeWindowMessage = 'Tempo restante: ' . abs($minutesDifference) . ' min';
                            } elseif ($minutesDifference === 0) {
                                $timeWindowMessage = 'Horário da consulta';
                            } else {
                                $timeWindowMessage = 'Início em ' . $minutesDifference . ' min';
                            }
                        } else {
                            // Fora da janela de tempo
                            if ($minutesDifference < -10) {
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

