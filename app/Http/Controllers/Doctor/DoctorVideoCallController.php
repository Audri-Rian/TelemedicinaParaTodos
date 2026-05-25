<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DoctorVideoCallController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (! $doctor) {
            return Inertia::render('Doctor/VideoCall', ['appointments' => []]);
        }

        $leadMinutes = (int) config('telemedicine.appointment.lead_minutes', 10);
        $trailingMinutes = (int) config('telemedicine.appointment.trailing_minutes', 10);
        $now = Carbon::now();
        $windowStart = $now->copy()->subMinutes($trailingMinutes);
        $windowEnd = $now->copy()->addMinutes($leadMinutes);

        $activeCallsByAppointment = Call::whereIn('status', [
            Call::STATUS_REQUESTED,
            Call::STATUS_RINGING,
            Call::STATUS_ACCEPTED,
        ])
            ->where('doctor_id', $doctor->id)
            ->get(['id', 'status', 'appointment_id'])
            ->keyBy('appointment_id');

        $appointments = Appointments::with('patient.user')
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_IN_PROGRESS,
            ])
            ->where(function ($query) use ($windowStart, $windowEnd) {
                $query->where('status', Appointments::STATUS_IN_PROGRESS)
                    ->orWhereBetween('scheduled_at', [$windowStart, $windowEnd]);
            })
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_at')
            ->get()
            ->map(function ($appointment) use ($now, $leadMinutes, $trailingMinutes, $activeCallsByAppointment) {
                $scheduledAt = Carbon::parse($appointment->scheduled_at);
                $minutesDiff = (int) round(($scheduledAt->timestamp - $now->timestamp) / 60);

                $canStartCall = $appointment->status === Appointments::STATUS_IN_PROGRESS
                    || ($minutesDiff >= -$trailingMinutes && $minutesDiff <= $leadMinutes);

                if ($appointment->status === Appointments::STATUS_IN_PROGRESS) {
                    $timeWindowMessage = 'Consulta em andamento';
                } elseif ($minutesDiff === 0) {
                    $timeWindowMessage = 'Horário da consulta';
                } elseif ($minutesDiff < 0) {
                    $timeWindowMessage = 'Tempo restante: '.abs($minutesDiff).' min';
                } else {
                    $timeWindowMessage = 'Início em '.$minutesDiff.' min';
                }

                $activeCall = $activeCallsByAppointment->get($appointment->id);

                return [
                    'id' => $appointment->id,
                    'scheduled_at' => $appointment->scheduled_at->format('Y-m-d H:i:s'),
                    'formatted_date' => $appointment->scheduled_at->format('d/m/Y'),
                    'formatted_time' => $appointment->scheduled_at->format('H:i'),
                    'status' => $appointment->status,
                    'can_start_call' => $canStartCall,
                    'time_window_message' => $timeWindowMessage,
                    'active_call' => $activeCall ? ['id' => $activeCall->id, 'status' => $activeCall->status] : null,
                    'patient' => [
                        'id' => $appointment->patient->user_id,
                        'name' => $appointment->patient->user->name,
                    ],
                ];
            });

        return Inertia::render('Doctor/VideoCall', [
            'appointments' => $appointments,
        ]);
    }
}
