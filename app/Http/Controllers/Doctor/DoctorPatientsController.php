<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorPatientsController extends Controller
{
    public function index(Request $request): Response
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            abort(403, 'Apenas médicos podem acessar esta página.');
        }

        return Inertia::render('Doctor/Patients', [
            'stats' => $this->buildStats($doctor->id),
            'upcomingPatients' => $this->buildUpcomingPatients($doctor->id),
            'patientHistory' => $this->buildPatientHistory($doctor->id),
        ]);
    }

    private function buildStats(string $doctorId): array
    {
        $totalPatients = Appointments::where('doctor_id', $doctorId)
            ->distinct('patient_id')
            ->count('patient_id');

        $activePatients = Appointments::where('doctor_id', $doctorId)
            ->whereIn('status', [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_COMPLETED,
            ])
            ->where('scheduled_at', '>=', now()->subDays(90))
            ->distinct('patient_id')
            ->count('patient_id');

        $consultedThisWeek = Appointments::where('doctor_id', $doctorId)
            ->where('status', Appointments::STATUS_COMPLETED)
            ->whereBetween('scheduled_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])
            ->count();

        $upcomingAppointments = Appointments::where('doctor_id', $doctorId)
            ->whereIn('status', [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
            ])
            ->where('scheduled_at', '>=', now())
            ->count();

        return [
            'totalPatients' => $totalPatients,
            'activePatients' => $activePatients,
            'consultedThisWeek' => $consultedThisWeek,
            'upcomingAppointments' => $upcomingAppointments,
        ];
    }

    private function buildUpcomingPatients(string $doctorId): array
    {
        return Appointments::with(['patient.user'])
            ->where('doctor_id', $doctorId)
            ->whereIn('status', [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
            ])
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get()
            ->map(fn (Appointments $appointment) => [
                'id' => $appointment->patient->id,
                'name' => $appointment->patient->user->name ?? 'Paciente',
                'avatar' => $appointment->patient->user->avatar ?? null,
                'initials' => $this->initials($appointment->patient->user->name ?? ''),
                'reason' => $appointment->notes,
                'scheduled_date' => $appointment->scheduled_at?->format('d/m/Y'),
                'scheduled_time' => $appointment->scheduled_at?->format('H:i'),
                'status' => $appointment->status,
                'status_class' => $this->statusClass($appointment->status),
                'channel' => 'video',
            ])
            ->values()
            ->all();
    }

    private function buildPatientHistory(string $doctorId): array
    {
        $patientIds = Appointments::where('doctor_id', $doctorId)
            ->distinct('patient_id')
            ->pluck('patient_id');

        $lastByPatient = Appointments::where('doctor_id', $doctorId)
            ->where('status', Appointments::STATUS_COMPLETED)
            ->whereIn('patient_id', $patientIds)
            ->orderByDesc('scheduled_at')
            ->get(['patient_id', 'scheduled_at', 'notes'])
            ->groupBy('patient_id')
            ->map(fn ($items) => $items->first());

        $nextByPatient = Appointments::where('doctor_id', $doctorId)
            ->whereIn('status', [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])
            ->where('scheduled_at', '>=', now())
            ->whereIn('patient_id', $patientIds)
            ->orderBy('scheduled_at')
            ->get(['patient_id', 'scheduled_at'])
            ->groupBy('patient_id')
            ->map(fn ($items) => $items->first());

        return Patient::with('user')
            ->whereIn('id', $patientIds)
            ->get()
            ->map(function (Patient $patient) use ($lastByPatient, $nextByPatient) {
                $last = $lastByPatient->get($patient->id);
                $next = $nextByPatient->get($patient->id);

                $status = $next ? 'agendado' : ($last ? 'historico' : 'sem-consultas');

                return [
                    'id' => $patient->id,
                    'name' => $patient->user->name ?? 'Paciente',
                    'avatar' => $patient->user->avatar ?? null,
                    'initials' => $this->initials($patient->user->name ?? ''),
                    'lastConsultation' => $last?->scheduled_at?->format('d/m/Y'),
                    'nextConsultation' => $next?->scheduled_at?->format('d/m/Y'),
                    'status' => $status,
                    'status_class' => $this->statusClass($status),
                    'notes' => $last?->notes,
                ];
            })
            ->values()
            ->all();
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        if (! $parts || $parts[0] === '') {
            return '';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first.$last);
    }

    private function statusClass(?string $status): string
    {
        return match ($status) {
            Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED, 'agendado' => 'bg-emerald-100 text-emerald-800',
            Appointments::STATUS_COMPLETED, 'historico' => 'bg-blue-100 text-blue-800',
            Appointments::STATUS_CANCELLED, Appointments::STATUS_NO_SHOW => 'bg-red-100 text-red-800',
            Appointments::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
