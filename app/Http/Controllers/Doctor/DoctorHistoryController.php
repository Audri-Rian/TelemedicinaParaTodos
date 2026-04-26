<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

        $appointments = Appointments::with(['patient.user'])
            ->where('doctor_id', $doctor->id)
            ->whereBetween('scheduled_at', [
                now()->subDays(30)->startOfDay(),
                now()->endOfDay(),
            ])
            ->orderByDesc('scheduled_at')
            ->get();

        return Inertia::render('Doctor/History', [
            'dayGroups' => $this->groupByDay($appointments),
        ]);
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
