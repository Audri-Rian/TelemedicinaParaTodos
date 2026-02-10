<?php

namespace App\Services\Doctor;

use App\Models\Appointments;
use App\Models\AvailabilitySlot;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityTimelineService
{
    /**
     * Retorna visão consolidada dos horários específicos (passados e futuros)
     * juntamente com KPIs para dashboards e cards informativos.
     */
    public function getOverview(Doctor $doctor, ?Carbon $start = null, ?Carbon $end = null): array
    {
        $windowDays = (int) config('telemedicine.availability.timeline_window_days', 30);
        $windowStart = ($start ?? Carbon::now()->subDays($windowDays))->startOfDay();
        $windowEnd = ($end ?? Carbon::now()->addDays($windowDays))->endOfDay();

        $slots = $doctor->availabilitySlots()
            ->where('type', AvailabilitySlot::TYPE_SPECIFIC)
            ->whereBetween('specific_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->where('is_active', true)
            ->with('location')
            ->orderBy('specific_date')
            ->orderBy('start_time')
            ->get();

        $appointments = Appointments::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->with(['patient.user'])
            ->get();

        $appointmentIndex = $appointments->keyBy(fn ($appointment) => $appointment->scheduled_at->format('Y-m-d H:i'));
        $now = Carbon::now();

        Carbon::setLocale(config('app.locale', 'pt_BR'));

        $timelineCollection = $slots
            ->groupBy(fn ($slot) => $slot->specific_date->format('Y-m-d'))
            ->map(function ($daySlots, $date) use ($appointmentIndex, $now) {
                $carbonDate = Carbon::parse($date);
                $slotItems = $daySlots->map(function ($slot) use ($appointmentIndex, $carbonDate, $now) {
                    $startTime = $this->formatTime($slot->start_time);
                    $endTime = $this->formatTime($slot->end_time);
                    $slotDateTime = Carbon::parse("{$carbonDate->format('Y-m-d')} {$startTime}");
                    $appointmentKey = "{$carbonDate->format('Y-m-d')} {$startTime}";
                    $appointment = $appointmentIndex->get($appointmentKey);

                    $statusMeta = $this->resolveStatusMeta($appointment, $slotDateTime);

                    return [
                        'id' => $slot->id,
                        'date' => $carbonDate->format('Y-m-d'),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'time_range' => "{$startTime} - {$endTime}",
                        'duration_minutes' => $this->calculateDuration($startTime, $endTime),
                        'location' => $slot->location ? [
                            'id' => $slot->location->id,
                            'name' => $slot->location->name,
                            'type' => $slot->location->type,
                            'type_label' => $slot->location->type_label,
                        ] : null,
                        'status' => $statusMeta,
                        'appointment' => $appointment ? [
                            'id' => $appointment->id,
                            'status' => $appointment->status,
                            'patient_name' => $appointment->patient?->user?->name ?? $appointment->patient?->full_name,
                            'patient_avatar' => $appointment->patient?->user?->avatar_url,
                        ] : null,
                        'is_past' => $slotDateTime->copy()->addMinutes($this->calculateDuration($startTime, $endTime))->isPast(),
                        'can_edit' => $slotDateTime->isFuture() && (!$appointment || !in_array($appointment->status, [
                            Appointments::STATUS_COMPLETED,
                            Appointments::STATUS_IN_PROGRESS,
                        ])),
                        'can_delete' => $slotDateTime->isFuture() && (!$appointment || !in_array($appointment->status, [
                            Appointments::STATUS_COMPLETED,
                            Appointments::STATUS_IN_PROGRESS,
                        ])),
                    ];
                });

                return [
                    'date' => $carbonDate->format('Y-m-d'),
                    'formatted_date' => $carbonDate->isoFormat('dddd, D [de] MMMM'),
                    'weekday' => $carbonDate->isoFormat('dddd'),
                    'is_past' => $carbonDate->endOfDay()->isPast(),
                    'slots' => $slotItems->values()->toArray(),
                ];
            })
            ->values();

        $flatSlots = $timelineCollection->flatMap(fn ($day) => collect($day['slots']));

        $summary = $this->buildSummary($doctor, $flatSlots, $appointments, $now);

        return [
            'timeline' => $timelineCollection->toArray(),
            'summary' => $summary,
            'window' => [
                'start' => $windowStart->toDateString(),
                'end' => $windowEnd->toDateString(),
            ],
            'locations' => $doctor->serviceLocations()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'type' => $location->type,
                        'type_label' => $location->type_label,
                    ];
                })
                ->toArray(),
        ];
    }

    private function resolveStatusMeta(?Appointments $appointment, Carbon $slotDateTime): array
    {
        if ($appointment) {
            return match ($appointment->status) {
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED => [
                    'code' => 'busy',
                    'label' => 'Ocupado',
                ],
                Appointments::STATUS_IN_PROGRESS => [
                    'code' => 'ongoing',
                    'label' => 'Em andamento',
                ],
                Appointments::STATUS_COMPLETED => [
                    'code' => 'completed',
                    'label' => 'Realizado',
                ],
                Appointments::STATUS_CANCELLED => [
                    'code' => 'cancelled',
                    'label' => 'Cancelado',
                ],
                Appointments::STATUS_NO_SHOW => [
                    'code' => 'no_show',
                    'label' => 'Ausência',
                ],
                default => [
                    'code' => 'busy',
                    'label' => 'Ocupado',
                ],
            };
        }

        if ($slotDateTime->isPast()) {
            return [
                'code' => 'expired',
                'label' => 'Expirado',
            ];
        }

        return [
            'code' => 'available',
            'label' => 'Disponível',
        ];
    }

    private function formatTime(?string $time): string
    {
        if (!$time) {
            return '00:00';
        }

        return Carbon::parse($time)->format('H:i');
    }

    private function calculateDuration(string $start, string $end): int
    {
        $startTime = Carbon::createFromFormat('H:i', $start);
        $endTime = Carbon::createFromFormat('H:i', $end);

        return $startTime->diffInMinutes($endTime);
    }

    private function buildSummary(
        Doctor $doctor,
        Collection $flatSlots,
        Collection $appointments,
        Carbon $now
    ): array {
        $futureSlots = $flatSlots->where('is_past', false);
        $pastSlots = $flatSlots->where('is_past', true);

        $currentWeekStart = $now->copy()->startOfWeek();
        $currentWeekEnd = $now->copy()->endOfWeek();
        $inCurrentWeek = $futureSlots->filter(function ($slot) use ($currentWeekStart, $currentWeekEnd) {
            $slotDate = Carbon::parse($slot['date']);
            return $slotDate->greaterThanOrEqualTo($currentWeekStart) && $slotDate->lessThanOrEqualTo($currentWeekEnd);
        });

        $nextWeekDays = (int) config('telemedicine.dashboard.next_week_days', 7);
        $sevenDaysAhead = $now->copy()->addDays($nextWeekDays);
        $nextSevenDays = $futureSlots->filter(function ($slot) use ($now, $sevenDaysAhead) {
            $slotDate = Carbon::parse($slot['date']);
            return $slotDate->greaterThanOrEqualTo($now) && $slotDate->lessThanOrEqualTo($sevenDaysAhead);
        });

        $nextSession = $appointments
            ->filter(fn ($appointment) => $appointment->scheduled_at->greaterThan($now))
            ->filter(fn ($appointment) => in_array($appointment->status, [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_IN_PROGRESS,
            ]))
            ->sortBy('scheduled_at')
            ->first();

        $lastSessionsLimit = (int) config('telemedicine.dashboard.last_sessions_limit', 4);
        $lastSessions = Appointments::query()
            ->where('doctor_id', $doctor->id)
            ->where('scheduled_at', '<', $now)
            ->orderByDesc('scheduled_at')
            ->with(['patient.user'])
            ->limit($lastSessionsLimit)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'date' => $appointment->scheduled_at->format('d/m'),
                    'time' => $appointment->scheduled_at->format('H:i'),
                    'status' => $appointment->status,
                    'patient_name' => $appointment->patient?->user?->name ?? $appointment->patient?->full_name,
                ];
            });

        return [
            'next_session' => $nextSession ? [
                'date' => $nextSession->scheduled_at->toDateString(),
                'time' => $nextSession->scheduled_at->format('H:i'),
                'weekday' => $nextSession->scheduled_at->isoFormat('dddd'),
                'patient_name' => $nextSession->patient?->user?->name ?? $nextSession->patient?->full_name,
                'status' => $nextSession->status,
            ] : null,
            'future_slots_count' => $futureSlots->count(),
            'available_this_week' => $inCurrentWeek->filter(fn ($slot) => $slot['status']['code'] === 'available')->count(),
            'next_seven_days' => [
                'total' => $nextSevenDays->count(),
                'available' => $nextSevenDays->filter(fn ($slot) => $slot['status']['code'] === 'available')->count(),
                'busy' => $nextSevenDays->filter(fn ($slot) => in_array($slot['status']['code'], ['busy', 'ongoing']))->count(),
            ],
            'past_slots_count' => $pastSlots->count(),
            'last_sessions' => $lastSessions->toArray(),
        ];
    }
}

