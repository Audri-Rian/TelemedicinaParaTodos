<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use App\Models\MedicalDocument;
use App\Presenters\CallSharedDocumentPresenter;
use App\Services\MedicalRecordService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DoctorVideoCallController extends Controller
{
    public function __construct(
        private readonly CallSharedDocumentPresenter $documentPresenter,
        private readonly MedicalRecordService $medicalRecordService,
    ) {}

    public function index(): Response
    {
        $user = Auth::user();
        $doctor = Doctor::where('user_id', $user->id)->select(['id'])->first();

        if (! $doctor) {
            return Inertia::render('Doctor/VideoCall', ['appointments' => []]);
        }

        $leadMinutes = (int) config('telemedicine.video_call.window_lead_minutes', 10);
        $trailingMinutes = (int) config('telemedicine.video_call.window_trailing_minutes', 10);
        $now = Carbon::now();
        $windowStart = $now->copy()->subMinutes($trailingMinutes);
        $windowEnd = $now->copy()->addMinutes($leadMinutes);

        // Inclui scheduled (type=scheduled, status=accepted) e ad-hoc entrantes (requested/ringing)
        $activeCallsByAppointment = Call::whereIn('status', [
            Call::STATUS_REQUESTED,
            Call::STATUS_RINGING,
            Call::STATUS_ACCEPTED,
        ])
            ->where('doctor_id', $doctor->id)
            ->whereNull('ended_at')
            ->get(['id', 'status', 'call_type', 'appointment_id'])
            ->keyBy('appointment_id');

        $appointments = Appointments::with('patient.user')
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_IN_PROGRESS,
            ])
            ->where(function ($query) use ($windowStart) {
                $query->where('status', Appointments::STATUS_IN_PROGRESS)
                    ->orWhere('scheduled_at', '>=', $windowStart);
            })
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_at')
            ->get();

        $documentsByAppointment = $appointments->isEmpty()
            ? collect()
            : MedicalDocument::whereIn('appointment_id', $appointments->pluck('id'))
                ->whereIn('visibility', [MedicalDocument::VISIBILITY_DOCTOR, MedicalDocument::VISIBILITY_SHARED])
                ->orderByDesc('created_at')
                ->select(['id', 'appointment_id', 'patient_id', 'category', 'name', 'file_type', 'file_size', 'visibility', 'created_at'])
                ->get()
                ->groupBy('appointment_id');

        $historyByPatient = $appointments->isEmpty()
            ? collect()
            : Appointments::whereIn('patient_id', $appointments->pluck('patient_id')->unique())
                ->where('doctor_id', $doctor->id)
                ->where('status', Appointments::STATUS_COMPLETED)
                ->orderByDesc('scheduled_at')
                ->get(['id', 'patient_id', 'scheduled_at', 'status', 'notes'])
                ->groupBy('patient_id');

        $clinicalAccessLogged = [];

        $appointments = $appointments
            ->map(function ($appointment) use ($now, $leadMinutes, $trailingMinutes, $activeCallsByAppointment, $documentsByAppointment, $historyByPatient, $user, &$clinicalAccessLogged) {
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
                } elseif ($minutesDiff < 60) {
                    $timeWindowMessage = 'Início em '.$minutesDiff.' min';
                } elseif ($minutesDiff < 1440) {
                    $hoursUntil = (int) round($minutesDiff / 60);
                    $timeWindowMessage = 'Início em '.$hoursUntil.($hoursUntil === 1 ? ' hora' : ' horas');
                } else {
                    $daysUntil = (int) round($minutesDiff / 1440);
                    $timeWindowMessage = 'Agendado para '.$daysUntil.($daysUntil === 1 ? ' dia' : ' dias');
                }

                $activeCall = $activeCallsByAppointment->get($appointment->id);

                // Resumo clínico só na janela da chamada — acesso a prontuário é auditado (LGPD)
                $clinicalSummary = null;
                if ($canStartCall) {
                    if (! isset($clinicalAccessLogged[$appointment->patient_id])) {
                        $this->medicalRecordService->logAccess($user, $appointment->patient, 'view', ['source' => 'video_call_panel']);
                        $clinicalAccessLogged[$appointment->patient_id] = true;
                    }

                    $clinicalSummary = [
                        'age' => $appointment->patient->age,
                        'gender' => $appointment->patient->gender,
                        'blood_type' => $appointment->patient->blood_type,
                        'allergies' => $appointment->patient->allergies,
                        'medical_history' => $appointment->patient->medical_history,
                        'current_medications' => $appointment->patient->current_medications,
                    ];
                }

                return [
                    'id' => $appointment->id,
                    'scheduled_at' => $appointment->scheduled_at->format('Y-m-d H:i:s'),
                    'formatted_date' => $appointment->scheduled_at->format('d/m/Y'),
                    'formatted_time' => $appointment->scheduled_at->format('H:i'),
                    'status' => $appointment->status,
                    'can_start_call' => $canStartCall,
                    'time_window_message' => $timeWindowMessage,
                    'active_call' => $activeCall ? [
                        'id' => $activeCall->id,
                        'status' => $activeCall->status,
                        'call_type' => $activeCall->call_type,
                    ] : null,
                    'patient' => [
                        'id' => $appointment->patient->user_id,
                        'patient_id' => $appointment->patient_id,
                        'name' => $appointment->patient->user->name,
                    ],
                    'chief_complaint' => $appointment->notes,
                    'clinical_summary' => $clinicalSummary,
                    'patient_history' => $historyByPatient
                        ->get($appointment->patient_id, collect())
                        ->reject(fn (Appointments $past) => $past->id === $appointment->id)
                        ->take(5)
                        ->map(fn (Appointments $past) => [
                            'id' => $past->id,
                            'date' => $past->scheduled_at->format('d/m/Y'),
                            'title' => 'Consulta finalizada',
                            'summary' => $past->notes,
                        ])
                        ->values()
                        ->all(),
                    'shared_documents' => $documentsByAppointment
                        ->get($appointment->id, collect())
                        ->map(fn (MedicalDocument $document) => $this->documentPresenter->forDoctor($document))
                        ->values()
                        ->all(),
                ];
            });

        return Inertia::render('Doctor/VideoCall', ['appointments' => $appointments]);
    }
}
