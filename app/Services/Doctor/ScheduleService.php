<?php

namespace App\Services\Doctor;

use App\Models\Doctor;
use App\Models\ServiceLocation;
use App\Models\AvailabilitySlot;
use App\Models\Doctor\BlockedDate;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScheduleService
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Carregar configuração completa da agenda do médico
     */
    public function getScheduleConfig(Doctor $doctor): array
    {
        // Carregar locais de atendimento
        $locations = $doctor->serviceLocations()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'type' => $location->type,
                    'type_label' => $location->type_label,
                    'address' => $location->address,
                    'phone' => $location->phone,
                    'description' => $location->description,
                ];
            });

        // Carregar slots de disponibilidade recorrentes
        $recurringSlots = $doctor->availabilitySlots()
            ->where('type', AvailabilitySlot::TYPE_RECURRING)
            ->where('is_active', true)
            ->with('location')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'day_of_week' => $slot->day_of_week,
                    'day_of_week_label' => $slot->day_of_week_label,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'location_id' => $slot->location_id,
                    'location' => $slot->location ? [
                        'id' => $slot->location->id,
                        'name' => $slot->location->name,
                        'type' => $slot->location->type,
                    ] : null,
                ];
            });

        // Carregar slots de disponibilidade específicos
        $today = Carbon::today();
        $nowTime = Carbon::now()->format('H:i:s');

        $specificSlots = $doctor->availabilitySlots()
            ->where('type', AvailabilitySlot::TYPE_SPECIFIC)
            ->where('is_active', true)
            ->where(function ($query) use ($today, $nowTime) {
                $query->where('specific_date', '>', $today->toDateString())
                    ->orWhere(function ($subQuery) use ($today, $nowTime) {
                        $subQuery->where('specific_date', $today->toDateString())
                            ->where('start_time', '>=', $nowTime);
                    });
            })
            ->with('location')
            ->orderBy('specific_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(function ($slot) {
                return $slot->specific_date->format('Y-m-d');
            })
            ->map(function ($slots, $date) {
                return [
                    'date' => $date,
                    'formatted_date' => Carbon::parse($date)->format('d/m/Y'),
                    'slots' => $slots->map(function ($slot) {
                        return [
                            'id' => $slot->id,
                            'start_time' => $slot->start_time,
                            'end_time' => $slot->end_time,
                            'location_id' => $slot->location_id,
                            'location' => $slot->location ? [
                                'id' => $slot->location->id,
                                'name' => $slot->location->name,
                                'type' => $slot->location->type,
                            ] : null,
                            'available' => true,
                        ];
                    })->toArray(),
                ];
            })
            ->values()
            ->toArray();

        // Carregar datas bloqueadas
        $blockedDates = $doctor->blockedDates()
            ->where('blocked_date', '>=', Carbon::today())
            ->orderBy('blocked_date')
            ->get()
            ->map(function ($blockedDate) {
                return [
                    'id' => $blockedDate->id,
                    'blocked_date' => $blockedDate->blocked_date->format('Y-m-d'),
                    'formatted_date' => $blockedDate->blocked_date->format('d/m/Y'),
                    'reason' => $blockedDate->reason,
                ];
            });

        return [
            'locations' => $locations,
            'recurring_slots' => $recurringSlots,
            'specific_slots' => $specificSlots,
            'blocked_dates' => $blockedDates,
        ];
    }

    /**
     * Salvar configuração completa da agenda (batch)
     */
    public function saveScheduleConfig(Doctor $doctor, array $data): array
    {
        return DB::transaction(function () use ($doctor, $data) {
            $errors = [];

            // Processar locais de atendimento
            if (isset($data['locations'])) {
                foreach ($data['locations'] as $locationData) {
                    try {
                        if (isset($locationData['id'])) {
                            // Atualizar local existente
                            $location = ServiceLocation::find($locationData['id']);
                            if ($location && $location->doctor_id === $doctor->id) {
                                $location->update($locationData);
                            }
                        } else {
                            // Criar novo local
                            $this->availabilityService->createServiceLocation(
                                $doctor,
                                $locationData['name'],
                                $locationData['type'],
                                $locationData['address'] ?? null,
                                $locationData['phone'] ?? null,
                                $locationData['description'] ?? null
                            );
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Erro ao processar local: " . $e->getMessage();
                    }
                }
            }

            // Processar slots recorrentes
            if (isset($data['recurring_slots'])) {
                foreach ($data['recurring_slots'] as $slotData) {
                    try {
                        if (isset($slotData['id'])) {
                            // Atualizar slot existente
                            $slot = AvailabilitySlot::find($slotData['id']);
                            if ($slot && $slot->doctor_id === $doctor->id) {
                                $slot->update($slotData);
                            }
                        } else {
                            // Criar novo slot recorrente
                            $this->availabilityService->createRecurringSlot(
                                $doctor,
                                $slotData['day_of_week'],
                                $slotData['start_time'],
                                $slotData['end_time'],
                                $slotData['location_id'] ?? null
                            );
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Erro ao processar slot recorrente: " . $e->getMessage();
                    }
                }
            }

            // Processar slots específicos
            if (isset($data['specific_slots'])) {
                foreach ($data['specific_slots'] as $dateSlots) {
                    foreach ($dateSlots['slots'] ?? [] as $slotData) {
                        try {
                            if (isset($slotData['id'])) {
                                // Atualizar slot existente
                                $slot = AvailabilitySlot::find($slotData['id']);
                                if ($slot && $slot->doctor_id === $doctor->id) {
                                    $slot->update($slotData);
                                }
                            } else {
                                // Criar novo slot específico
                                $this->availabilityService->createSpecificSlot(
                                    $doctor,
                                    Carbon::parse($dateSlots['date']),
                                    $slotData['start_time'],
                                    $slotData['end_time'],
                                    $slotData['location_id'] ?? null
                                );
                            }
                        } catch (\Exception $e) {
                            $errors[] = "Erro ao processar slot específico: " . $e->getMessage();
                        }
                    }
                }
            }

            // Processar datas bloqueadas
            if (isset($data['blocked_dates'])) {
                foreach ($data['blocked_dates'] as $blockedDateData) {
                    try {
                        if (!isset($blockedDateData['id'])) {
                            // Criar nova data bloqueada
                            BlockedDate::create([
                                'doctor_id' => $doctor->id,
                                'blocked_date' => $blockedDateData['blocked_date'],
                                'reason' => $blockedDateData['reason'] ?? null,
                            ]);
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Erro ao processar data bloqueada: " . $e->getMessage();
                    }
                }
            }

            if (!empty($errors)) {
                throw new \Exception("Erros ao salvar configuração: " . implode(', ', $errors));
            }

            return $this->getScheduleConfig($doctor);
        });
    }

    /**
     * Retornar disponibilidade para uma data específica
     * Considera slots recorrentes, específicos, appointments e datas bloqueadas
     */
    public function getAvailabilityForDate(Doctor $doctor, Carbon $date): array
    {
        // Verificar se a data está bloqueada
        $isBlocked = $doctor->blockedDates()
            ->where('blocked_date', $date->format('Y-m-d'))
            ->exists();

        if ($isBlocked) {
            return [
                'date' => $date->format('Y-m-d'),
                'formatted_date' => $date->format('d/m/Y'),
                'is_blocked' => true,
                'available_slots' => [],
                'message' => 'Esta data está bloqueada para atendimento.',
            ];
        }

        // Usar o AvailabilityService para buscar slots disponíveis
        $availableSlots = $this->availabilityService->getAvailableSlotsForDate($doctor, $date);

        return [
            'date' => $date->format('Y-m-d'),
            'formatted_date' => $date->format('d/m/Y'),
            'is_blocked' => false,
            'available_slots' => $availableSlots,
        ];
    }

    /**
     * Garante que um médico possua uma disponibilidade inicial caso ainda não tenha configurado.
     */
    public function ensureDefaultAvailability(Doctor $doctor): void
    {
        if ($doctor->availabilitySlots()->exists()) {
            return;
        }

        $defaults = config('telemedicine.doctor_defaults', []);
        $workDays = $defaults['work_days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        if (!empty($defaults['include_saturday']) && !in_array('saturday', $workDays, true)) {
            $workDays[] = 'saturday';
        }

        $workHours = $defaults['work_hours'] ?? ['start' => '08:00', 'end' => '18:00'];
        $slotDuration = (int) ($defaults['slot_duration_minutes'] ?? 45);
        $lunchBreak = $defaults['lunch_break'] ?? ['start' => '12:00', 'end' => '14:00'];

        $teleLocation = $doctor->serviceLocations()
            ->where('type', ServiceLocation::TYPE_TELECONSULTATION)
            ->first();

        if (!$teleLocation) {
            $teleLocation = $this->availabilityService->createServiceLocation(
                $doctor,
                $defaults['telehealth_location']['name'] ?? 'Teleconsulta (Padrão)',
                ServiceLocation::TYPE_TELECONSULTATION,
                $defaults['telehealth_location']['address'] ?? null,
                $defaults['telehealth_location']['phone'] ?? null,
                $defaults['telehealth_location']['description'] ?? 'Atendimento remoto via videoconferência.'
            );
        }

        $availabilitySchedule = $doctor->availability_schedule ?? [];
        $allDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

        foreach ($allDays as $day) {
            if (!in_array($day, $workDays, true)) {
                $availabilitySchedule[$day] = $availabilitySchedule[$day] ?? null;
                continue;
            }

            $start = $workHours['start'] ?? '08:00';
            $end = $workHours['end'] ?? '18:00';
            $slots = $this->generateDefaultSlots($start, $end, $slotDuration, $lunchBreak);

            $availabilitySchedule[$day] = [
                'start' => $start,
                'end' => $end,
                'slots' => $slots,
            ];

            $this->availabilityService->createRecurringSlot(
                $doctor,
                $day,
                $start,
                $end,
                $teleLocation->id
            );
        }

        $doctor->availability_schedule = $availabilitySchedule;
        $doctor->save();
    }

    /**
     * Gera slots de horário respeitando duração e intervalo configurados.
     */
    private function generateDefaultSlots(
        string $startTime,
        string $endTime,
        int $slotDuration,
        ?array $lunchBreak = null
    ): array {
        $slots = [];

        [$startHour, $startMin] = explode(':', $startTime);
        [$endHour, $endMin] = explode(':', $endTime);
        $startMinutes = (int)$startHour * 60 + (int)$startMin;
        $endMinutes = (int)$endHour * 60 + (int)$endMin;

        $lunchStart = null;
        $lunchEnd = null;

        if ($lunchBreak && isset($lunchBreak['start'], $lunchBreak['end'])) {
            [$lunchStartHour, $lunchStartMin] = explode(':', $lunchBreak['start']);
            [$lunchEndHour, $lunchEndMin] = explode(':', $lunchBreak['end']);
            $lunchStart = (int)$lunchStartHour * 60 + (int)$lunchStartMin;
            $lunchEnd = (int)$lunchEndHour * 60 + (int)$lunchEndMin;
        }

        $currentMinutes = $startMinutes;
        while ($currentMinutes + $slotDuration <= $endMinutes) {
            $slotEnd = $currentMinutes + $slotDuration;

            if ($lunchStart !== null && $lunchEnd !== null) {
                $overlapsLunch = ($currentMinutes >= $lunchStart && $currentMinutes < $lunchEnd)
                    || ($slotEnd > $lunchStart && $slotEnd <= $lunchEnd)
                    || ($currentMinutes < $lunchStart && $slotEnd > $lunchEnd);

                if ($overlapsLunch) {
                    $currentMinutes += $slotDuration;
                    continue;
                }
            }

            $hours = floor($currentMinutes / 60);
            $minutes = $currentMinutes % 60;
            $slots[] = sprintf('%02d:%02d', $hours, $minutes);

            $currentMinutes += $slotDuration;
        }

        return $slots;
    }

    /**
     * Validar conflitos de horários
     */
    public function validateSlotConflicts(
        Doctor $doctor,
        string $startTime,
        string $endTime,
        ?string $dayOfWeek = null,
        ?Carbon $specificDate = null,
        ?string $locationId = null,
        ?string $excludeSlotId = null
    ): bool {
        $query = $doctor->availabilitySlots()
            ->where('is_active', true)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        // Se é slot recorrente
        if ($dayOfWeek) {
            $query->where('type', AvailabilitySlot::TYPE_RECURRING)
                  ->where('day_of_week', $dayOfWeek);
        }

        // Se é slot específico
        if ($specificDate) {
            $query->where('type', AvailabilitySlot::TYPE_SPECIFIC)
                  ->where('specific_date', $specificDate->format('Y-m-d'));
        }

        // Mesmo local se especificado
        if ($locationId) {
            $query->where(function ($q) use ($locationId) {
                $q->where('location_id', $locationId)
                  ->orWhereNull('location_id');
            });
        }

        // Excluir o próprio slot se estiver atualizando
        if ($excludeSlotId) {
            $query->where('id', '!=', $excludeSlotId);
        }

        return !$query->exists();
    }
}

