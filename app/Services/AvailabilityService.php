<?php

namespace App\Services;

use App\Models\AvailabilitySlot;
use App\Models\Doctor;
use App\Models\ServiceLocation;
use App\Models\Appointments;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Buscar slots de disponibilidade para um médico em uma data específica
     */
    public function getAvailableSlotsForDate(Doctor $doctor, Carbon $date): array
    {
        $slots = [];

        // Buscar slots recorrentes para o dia da semana
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.
        $recurringSlots = $doctor->availabilitySlots()
            ->where('type', AvailabilitySlot::TYPE_RECURRING)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        // Buscar slots específicos para a data
        $specificSlots = $doctor->availabilitySlots()
            ->where('type', AvailabilitySlot::TYPE_SPECIFIC)
            ->where('specific_date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->get();

        // Combinar slots
        $allSlots = $recurringSlots->merge($specificSlots);

        // Buscar appointments já agendados para essa data
        $existingAppointments = Appointments::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('scheduled_at', $date->toDateString())
            ->whereIn('status', [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_IN_PROGRESS
            ])
            ->pluck('scheduled_at')
            ->map(fn ($dt) => Carbon::parse($dt)->format('H:i'))
            ->toArray();

        // Gerar slots disponíveis baseado nos intervalos
        foreach ($allSlots as $slot) {
            $timeSlots = $this->generateTimeSlotsFromInterval(
                $slot->start_time,
                $slot->end_time
            );

            // Filtrar slots ocupados
            $filteredSlots = array_filter($timeSlots, function ($slotTime) use ($existingAppointments, $date) {
                // Remover slots ocupados
                if (in_array($slotTime, $existingAppointments)) {
                    return false;
                }

                // Se for hoje, remover slots que já passaram
                if ($date->isToday()) {
                    try {
                        $slotDateTime = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $slotTime);
                        $minAllowedTime = Carbon::now()->addMinutes(5);
                        return $slotDateTime->greaterThan($minAllowedTime);
                    } catch (\Exception $e) {
                        return true;
                    }
                }

                return true;
            });

            // Adicionar informações do local se existir
            foreach ($filteredSlots as $slotTime) {
                $slotData = [
                    'time' => $slotTime,
                    'location_id' => $slot->location_id,
                ];

                if ($slot->location) {
                    $slotData['location'] = [
                        'id' => $slot->location->id,
                        'name' => $slot->location->name,
                        'type' => $slot->location->type,
                        'type_label' => $slot->location->type_label,
                    ];
                }

                $slots[] = $slotData;
            }
        }

        return $slots;
    }

    /**
     * Buscar todas as datas disponíveis para um médico em um período
     */
    public function getAvailableDates(Doctor $doctor, Carbon $startDate, Carbon $endDate): array
    {
        $availableDates = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $slots = $this->getAvailableSlotsForDate($doctor, $currentDate);
            
            if (!empty($slots)) {
                $availableDates[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'formatted_date' => $currentDate->format('d/m/Y'),
                    'available_slots' => $slots,
                ];
            }

            $currentDate->addDay();
        }

        return $availableDates;
    }

    /**
     * Criar um slot de disponibilidade recorrente
     */
    public function createRecurringSlot(
        Doctor $doctor,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?string $locationId = null
    ): AvailabilitySlot {
        return AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'location_id' => $locationId,
            'type' => AvailabilitySlot::TYPE_RECURRING,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => true,
        ]);
    }

    /**
     * Criar um slot de disponibilidade específico (data específica)
     */
    public function createSpecificSlot(
        Doctor $doctor,
        Carbon|string $specificDate,
        string $startTime,
        string $endTime,
        ?string $locationId = null
    ): AvailabilitySlot {
        $dateString = $specificDate instanceof Carbon ? $specificDate->format('Y-m-d') : $specificDate;

        return AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'location_id' => $locationId,
            'type' => AvailabilitySlot::TYPE_SPECIFIC,
            'specific_date' => $dateString,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => true,
        ]);
    }

    /**
     * Criar um local de atendimento
     */
    public function createServiceLocation(
        Doctor $doctor,
        string $name,
        string $type,
        ?string $address = null,
        ?string $phone = null,
        ?string $description = null
    ): ServiceLocation {
        return ServiceLocation::create([
            'doctor_id' => $doctor->id,
            'name' => $name,
            'type' => $type,
            'address' => $address,
            'phone' => $phone,
            'description' => $description,
            'is_active' => true,
        ]);
    }

    /**
     * Gerar slots de tempo a partir de um intervalo
     * Gera slots de 45 minutos por padrão
     */
    private function generateTimeSlotsFromInterval(
        string $startTime,
        string $endTime
    ): array {
        $slots = [];
        
        [$startHour, $startMin] = explode(':', $startTime);
        [$endHour, $endMin] = explode(':', $endTime);
        
        $startMinutes = (int)$startHour * 60 + (int)$startMin;
        $endMinutes = (int)$endHour * 60 + (int)$endMin;
        
        // Intervalo de almoço: 12:00-14:00 (720-840 minutos)
        $lunchStart = 12 * 60;
        $lunchEnd = 14 * 60;
        
        $currentMinutes = $startMinutes;
        $slotDuration = 45; // 45 minutos
        
        while ($currentMinutes + $slotDuration <= $endMinutes) {
            $slotEnd = $currentMinutes + $slotDuration;
            
            // Pular intervalo de almoço
            if (!($currentMinutes >= $lunchStart && $currentMinutes < $lunchEnd) &&
                !($slotEnd > $lunchStart && $slotEnd <= $lunchEnd) &&
                !($currentMinutes < $lunchStart && $slotEnd > $lunchEnd)) {
                
                $hours = floor($currentMinutes / 60);
                $minutes = $currentMinutes % 60;
                $slots[] = sprintf('%02d:%02d', $hours, $minutes);
            }
            
            $currentMinutes += $slotDuration;
        }
        
        return $slots;
    }

    /**
     * Verificar se um médico está disponível em uma data e horário específicos
     */
    public function isDoctorAvailableAt(Doctor $doctor, Carbon $dateTime): bool
    {
        $date = $dateTime->copy()->startOfDay();
        $time = $dateTime->format('H:i');
        
        $availableSlots = $this->getAvailableSlotsForDate($doctor, $date);
        
        foreach ($availableSlots as $slot) {
            if ($slot['time'] === $time) {
                return true;
            }
        }
        
        return false;
    }
}

