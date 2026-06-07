<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Presenters\CallSharedDocumentPresenter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PatientVideoCallController extends Controller
{
    public function __construct(private readonly CallSharedDocumentPresenter $documentPresenter) {}

    public function index(): Response
    {
        $user = Auth::user();
        $patient = Patient::where('user_id', $user->id)->select(['id'])->first();

        if (! $patient) {
            return Inertia::render('Patient/VideoCall', ['users' => []]);
        }

        $leadMinutes = (int) config('telemedicine.video_call.window_lead_minutes', 10);
        $trailingMinutes = (int) config('telemedicine.video_call.window_trailing_minutes', 10);
        $relationshipDays = (int) config('telemedicine.video_call.ad_hoc_relationship_days', 7);
        $appointmentsHistoryLimit = max(1, (int) config('telemedicine.video_call.patient_history_limit', 10));
        $now = Carbon::now();

        $doctorIds = Appointments::where('patient_id', $patient->id)
            ->where('status', '!=', Appointments::STATUS_CANCELLED)
            ->distinct()
            ->pluck('doctor_id');

        if ($doctorIds->isEmpty()) {
            return Inertia::render('Patient/VideoCall', ['users' => []]);
        }

        $appointmentsByDoctor = Appointments::whereIn('doctor_id', $doctorIds)
            ->where('patient_id', $patient->id)
            ->where('status', '!=', Appointments::STATUS_CANCELLED)
            ->orderBy('scheduled_at', 'desc')
            ->limit($appointmentsHistoryLimit * $doctorIds->count())
            ->select(['id', 'doctor_id', 'status', 'scheduled_at'])
            ->get()
            ->groupBy('doctor_id');

        $activeScheduledByDoctor = Call::where('call_type', Call::TYPE_SCHEDULED)
            ->whereIn('doctor_id', $doctorIds)
            ->where('patient_id', $patient->id)
            ->where('status', Call::STATUS_ACCEPTED)
            ->whereNull('ended_at')
            ->pluck('doctor_id')
            ->flip();

        $recentConsultationByDoctor = Appointments::whereIn('doctor_id', $doctorIds)
            ->where('patient_id', $patient->id)
            ->whereNotIn('status', [Appointments::STATUS_CANCELLED])
            ->where('ended_at', '>=', now()->subDays($relationshipDays))
            ->pluck('doctor_id')
            ->flip();

        $doctors = Doctor::with('user:id,name')
            ->whereIn('id', $doctorIds)
            ->active()
            ->get()
            ->map(function ($doctor) use (
                $appointmentsByDoctor,
                $activeScheduledByDoctor,
                $appointmentsHistoryLimit,
                $leadMinutes,
                $now,
                $recentConsultationByDoctor,
                $trailingMinutes
            ) {
                $allAppointments = $appointmentsByDoctor->get($doctor->id, collect());

                $primaryAppointment = null;
                $canStartCall = false;
                $timeWindowMessage = null;

                $primaryAppointment = $allAppointments->first(fn ($a) => $a->status === Appointments::STATUS_IN_PROGRESS);

                if (! $primaryAppointment) {
                    $primaryAppointment = $allAppointments->first(function ($a) use ($now, $trailingMinutes, $leadMinutes) {
                        if (! in_array($a->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])) {
                            return false;
                        }
                        $diff = (int) round(($a->scheduled_at->timestamp - $now->timestamp) / 60);

                        return $diff >= -$trailingMinutes && $diff <= $leadMinutes;
                    });
                }

                if (! $primaryAppointment) {
                    $primaryAppointment = $allAppointments->first(
                        fn ($a) => in_array($a->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])
                            && $a->scheduled_at->greaterThan($now)
                    );
                }

                if (! $primaryAppointment) {
                    $primaryAppointment = $allAppointments->first(
                        fn ($a) => in_array($a->status, [Appointments::STATUS_COMPLETED, Appointments::STATUS_NO_SHOW])
                            && $a->scheduled_at->lessThan($now)
                    );
                }

                if ($primaryAppointment) {
                    $scheduledAt = Carbon::parse($primaryAppointment->scheduled_at);
                    $minutesDiff = (int) round(($scheduledAt->timestamp - $now->timestamp) / 60);

                    if ($primaryAppointment->status === Appointments::STATUS_IN_PROGRESS) {
                        $canStartCall = $primaryAppointment->isWithinInProgressWindow();
                        $timeWindowMessage = $canStartCall ? 'Consulta em andamento' : 'Consulta encerrada — janela expirada';
                    } elseif ($minutesDiff >= -$trailingMinutes && $minutesDiff <= $leadMinutes
                        && in_array($primaryAppointment->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])) {
                        $canStartCall = true;
                        if ($minutesDiff < 0) {
                            $timeWindowMessage = 'Tempo restante: '.abs($minutesDiff).' min';
                        } elseif ($minutesDiff === 0) {
                            $timeWindowMessage = 'Horário da consulta';
                        } else {
                            $timeWindowMessage = 'Início em '.$minutesDiff.' min';
                        }
                    } else {
                        if ($primaryAppointment->status === Appointments::STATUS_COMPLETED) {
                            $timeWindowMessage = 'Consulta finalizada';
                        } elseif ($primaryAppointment->status === Appointments::STATUS_NO_SHOW) {
                            $timeWindowMessage = 'Consulta não comparecida';
                        } elseif ($minutesDiff < -$trailingMinutes) {
                            $timeWindowMessage = 'Janela de tempo expirada';
                        } else {
                            $daysUntil = (int) $now->diffInDays($scheduledAt, false);
                            if ($daysUntil > 0) {
                                $timeWindowMessage = 'Agendado para '.$daysUntil.($daysUntil === 1 ? ' dia' : ' dias');
                            } else {
                                $hoursUntil = (int) $now->diffInHours($scheduledAt, false);
                                if ($hoursUntil > 0) {
                                    $timeWindowMessage = 'Início em '.$hoursUntil.($hoursUntil === 1 ? ' hora' : ' horas');
                                } else {
                                    $minsUntil = (int) ceil($now->diffInMinutes($scheduledAt, false));
                                    $timeWindowMessage = 'Início em '.$minsUntil.' min';
                                }
                            }
                        }
                    }
                }

                $hasActiveScheduledCall = $activeScheduledByDoctor->has($doctor->id);
                $hasRecentConsultation = $recentConsultationByDoctor->has($doctor->id);

                $appointmentsList = $allAppointments->take($appointmentsHistoryLimit)->map(fn ($a) => [
                    'id' => $a->id,
                    'scheduled_at' => $a->scheduled_at->format('Y-m-d H:i:s'),
                    'formatted_date' => $a->scheduled_at->format('d/m/Y'),
                    'formatted_time' => $a->scheduled_at->format('H:i'),
                    'status' => $a->status,
                ])->toArray();

                return [
                    'id' => $doctor->user->id,
                    'doctor_id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'hasAppointment' => $primaryAppointment !== null,
                    'canStartCall' => $canStartCall,
                    'hasActiveScheduledCall' => $hasActiveScheduledCall,
                    'hasRecentConsultation' => $hasRecentConsultation,
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

        $doctors = $this->attachSharedDocuments($doctors, $patient);

        return Inertia::render('Patient/VideoCall', ['users' => $doctors]);
    }

    private function attachSharedDocuments(Collection $doctors, Patient $patient): Collection
    {
        $appointmentIds = $doctors->pluck('appointment.id')->filter()->values();

        $documentsByAppointment = $appointmentIds->isEmpty()
            ? collect()
            : MedicalDocument::whereIn('appointment_id', $appointmentIds)
                ->where('patient_id', $patient->id)
                ->whereIn('visibility', [MedicalDocument::VISIBILITY_PATIENT, MedicalDocument::VISIBILITY_SHARED])
                ->orderByDesc('created_at')
                ->select(['id', 'appointment_id', 'patient_id', 'category', 'name', 'file_type', 'file_size', 'visibility', 'created_at'])
                ->get()
                ->groupBy('appointment_id');

        return $doctors->map(function (array $entry) use ($documentsByAppointment) {
            if ($entry['appointment'] !== null) {
                $entry['appointment']['shared_documents'] = $documentsByAppointment
                    ->get($entry['appointment']['id'], collect())
                    ->map(fn (MedicalDocument $document) => $this->documentPresenter->forPatient($document))
                    ->values()
                    ->all();
            }

            return $entry;
        });
    }
}
