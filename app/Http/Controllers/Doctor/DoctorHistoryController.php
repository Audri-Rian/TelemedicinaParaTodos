<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\MedicalDocument;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DoctorHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            abort(403, 'Apenas médicos podem acessar esta página.');
        }

        $period = $this->period($request);
        $status = $this->status($request);

        $baseAppointmentsQuery = Appointments::query()
            ->select([
                'id',
                'doctor_id',
                'patient_id',
                'scheduled_at',
                'started_at',
                'ended_at',
                'status',
                'notes',
            ])
            ->with(['patient:id,user_id,date_of_birth,gender', 'patient.user:id,name'])
            ->where('doctor_id', $doctor->id)
            ->whereBetween('scheduled_at', [
                $period['start'],
                $period['end'],
            ]);

        $allPeriodAppointments = (clone $baseAppointmentsQuery)->get();

        $appointments = $status === 'all'
            ? $allPeriodAppointments->sortByDesc('scheduled_at')->values()
            : $allPeriodAppointments->filter(
                fn (Appointments $a) => $this->matchesStatus($a->status, $status)
            )->sortByDesc('scheduled_at')->values();

        $documentsBaseQuery = MedicalDocument::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('created_at', [$period['start'], $period['end']]);

        $documentsCount = (clone $documentsBaseQuery)->count();

        $latestDocuments = (clone $documentsBaseQuery)
            ->select(['id', 'patient_id', 'name', 'category', 'created_at'])
            ->with('patient.user:id,name')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (MedicalDocument $document) => [
                'id' => $document->id,
                'name' => $document->name,
                'category' => $document->category,
                'patient_name' => $document->patient?->user?->name ?? 'Paciente',
                'created_at' => $document->created_at?->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('Doctor/History', [
            'dayGroups' => $this->groupByDay($appointments),
            'documentsSummary' => [
                'count' => $documentsCount,
                'latest' => $latestDocuments,
            ],
            'periodSummary' => $this->periodSummary($allPeriodAppointments),
            'pendingSummary' => $this->pendingSummary($doctor->id),
            'filters' => [
                'period' => $period['key'],
                'status' => $status,
                'periodLabel' => $period['label'],
                'documentsPeriodDays' => $period['days'],
            ],
        ]);
    }

    private function period(Request $request): array
    {
        $key = $request->string('period')->toString();

        return match ($key) {
            'today' => [
                'key' => 'today',
                'label' => 'Hoje',
                'days' => 1,
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            '7d' => [
                'key' => '7d',
                'label' => '7 dias',
                'days' => 7,
                'start' => now()->subDays(6)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            default => [
                'key' => '30d',
                'label' => '30 dias',
                'days' => 30,
                'start' => now()->subDays(29)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
        };
    }

    private function status(Request $request): string
    {
        $status = $request->string('status', 'all')->toString();

        return in_array($status, ['all', 'confirmed', 'concluded', 'missed', 'in_progress', 'cancelled', 'rescheduled'], true)
            ? $status
            : 'all';
    }

    private function periodSummary(Collection $appointments): array
    {
        $total = $appointments->count();
        $statuses = $appointments->countBy('status');
        $confirmed = $statuses->get(Appointments::STATUS_SCHEDULED, 0);
        $rescheduled = $statuses->get(Appointments::STATUS_RESCHEDULED, 0);
        $concluded = $statuses->get(Appointments::STATUS_COMPLETED, 0);
        $inProgress = $statuses->get(Appointments::STATUS_IN_PROGRESS, 0);
        $missed = $statuses->get(Appointments::STATUS_NO_SHOW, 0);
        $cancelled = $statuses->get(Appointments::STATUS_CANCELLED, 0);
        $attended = $confirmed + $rescheduled + $concluded + $inProgress;
        $durations = $appointments
            ->filter(fn (Appointments $appointment) => $appointment->started_at && $appointment->ended_at)
            ->map(fn (Appointments $appointment) => (int) $appointment->started_at->diffInMinutes($appointment->ended_at));
        $averageDuration = $durations->isNotEmpty()
            ? (int) round($durations->average())
            : (int) config('telemedicine.appointment.default_duration_minutes', 30);

        return [
            'total' => $total,
            'confirmationRate' => $total > 0 ? (int) round(($attended / $total) * 100) : 0,
            'missed' => $missed,
            'averageDuration' => "{$averageDuration} min",
            'statusCounts' => [
                'all' => $total,
                'confirmed' => $confirmed,
                'concluded' => $concluded,
                'missed' => $missed,
                'in_progress' => $inProgress,
                'cancelled' => $cancelled,
                'rescheduled' => $rescheduled,
            ],
        ];
    }

    private function pendingSummary(string $doctorId): array
    {
        return Cache::remember("doctor_pending_{$doctorId}", 60, function () use ($doctorId) {
            return [
                'unfinishedRecords' => Appointments::query()
                    ->where('doctor_id', $doctorId)
                    ->where('status', Appointments::STATUS_IN_PROGRESS)
                    ->count(),
                'unsignedPrescriptions' => Prescription::query()
                    ->where('doctor_id', $doctorId)
                    ->where('status', Prescription::STATUS_ACTIVE)
                    ->where('signature_status', Prescription::SIGNATURE_UNSIGNED)
                    ->count(),
                'reschedulesWaiting' => Appointments::query()
                    ->where('doctor_id', $doctorId)
                    ->where('status', Appointments::STATUS_RESCHEDULED)
                    ->whereBetween('scheduled_at', [
                        now()->startOfDay(),
                        now()->addDays(29)->endOfDay(),
                    ])
                    ->count(),
            ];
        });
    }

    private function matchesStatus(string $appointmentStatus, string $filterStatus): bool
    {
        return match ($filterStatus) {
            'confirmed' => $appointmentStatus === Appointments::STATUS_SCHEDULED,
            'concluded' => $appointmentStatus === Appointments::STATUS_COMPLETED,
            'missed' => $appointmentStatus === Appointments::STATUS_NO_SHOW,
            'in_progress' => $appointmentStatus === Appointments::STATUS_IN_PROGRESS,
            'cancelled' => $appointmentStatus === Appointments::STATUS_CANCELLED,
            'rescheduled' => $appointmentStatus === Appointments::STATUS_RESCHEDULED,
            default => true,
        };
    }

    private function groupByDay(Collection $appointments): array
    {
        return $appointments
            ->groupBy(fn (Appointments $a) => $a->scheduled_at->format('Y-m-d'))
            ->map(function (Collection $group, string $date) {
                $day = Carbon::parse($date);
                $statuses = $group->countBy('status');

                return [
                    'id' => $date,
                    'label' => $this->dayLabel($day),
                    'dateLabel' => $day->isoFormat('D MMM'),
                    'summary' => $this->summarize($statuses),
                    'appointments' => $group->map(fn (Appointments $a) => [
                        'id' => $a->id,
                        'time' => $a->scheduled_at->format('H:i'),
                        'patient' => $a->patient->user->name ?? 'Paciente',
                        'detail' => $a->notes ?? '',
                        'duration' => $this->duration($a),
                        'age' => $a->patient->age ? $a->patient->age.'a' : '',
                        'gender' => $this->genderLabel($a->patient->gender ?? null),
                        'initials' => $this->initials($a->patient->user->name ?? ''),
                        'status' => $this->statusKey($a->status),
                        'statusLabel' => $this->statusLabel($a->status),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function dayLabel(Carbon $day): string
    {
        if ($day->isToday()) {
            return 'Hoje';
        }
        if ($day->isYesterday()) {
            return 'Ontem';
        }

        return $day->isoFormat('dddd');
    }

    private function summarize(Collection $statuses): string
    {
        $parts = [];
        $scheduled = $statuses->get(Appointments::STATUS_SCHEDULED, 0) + $statuses->get(Appointments::STATUS_RESCHEDULED, 0);
        if ($scheduled) {
            $parts[] = "{$scheduled} agendadas";
        }
        if ($completed = $statuses->get(Appointments::STATUS_COMPLETED, 0)) {
            $parts[] = "{$completed} concluídas";
        }
        if ($noShow = $statuses->get(Appointments::STATUS_NO_SHOW, 0)) {
            $parts[] = "{$noShow} falta".($noShow > 1 ? 's' : '');
        }
        if ($cancelled = $statuses->get(Appointments::STATUS_CANCELLED, 0)) {
            $parts[] = "{$cancelled} cancelada".($cancelled > 1 ? 's' : '');
        }

        return implode(' · ', $parts);
    }

    private function duration(Appointments $appointment): string
    {
        if ($appointment->started_at && $appointment->ended_at) {
            $minutes = $appointment->started_at->diffInMinutes($appointment->ended_at);

            return "{$minutes} min";
        }

        return (int) config('telemedicine.appointment.default_duration_minutes', 30).' min';
    }

    private function genderLabel(?string $gender): string
    {
        return match (strtolower((string) $gender)) {
            'male', 'masculino', 'm' => 'M',
            'female', 'feminino', 'f' => 'F',
            default => '',
        };
    }

    private function statusKey(?string $status): string
    {
        return match ($status) {
            Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED => 'confirmed',
            Appointments::STATUS_COMPLETED => 'concluded',
            Appointments::STATUS_NO_SHOW => 'missed',
            Appointments::STATUS_IN_PROGRESS => 'in_progress',
            Appointments::STATUS_CANCELLED => 'cancelled',
            default => 'confirmed',
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            Appointments::STATUS_SCHEDULED => 'Agendada',
            Appointments::STATUS_RESCHEDULED => 'Reagendada',
            Appointments::STATUS_COMPLETED => 'Concluída',
            Appointments::STATUS_NO_SHOW => 'Falta',
            Appointments::STATUS_IN_PROGRESS => 'Em andamento',
            Appointments::STATUS_CANCELLED => 'Cancelada',
            default => 'Agendada',
        };
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
}
