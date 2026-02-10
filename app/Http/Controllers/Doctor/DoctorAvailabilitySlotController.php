<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreAvailabilitySlotRequest;
use App\Http\Requests\Doctor\UpdateAvailabilitySlotRequest;
use App\Models\Doctor;
use App\Models\AvailabilitySlot;
use App\Services\AvailabilityService;
use App\Services\Doctor\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorAvailabilitySlotController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected ScheduleService $scheduleService
    ) {}

    /**
     * Criar novo slot de disponibilidade
     * POST /api/doctors/{doctor}/availability
     */
    public function store(StoreAvailabilitySlotRequest $request, Doctor $doctor): JsonResponse
    {
        // Autorização
        if (auth()->user()->doctor->id !== $doctor->id) {
            abort(403, 'Você não tem permissão para criar slots para este médico.');
        }

        $validated = $request->validated();

        // Validar conflitos de horários
        $hasConflict = $this->scheduleService->validateSlotConflicts(
            $doctor,
            $validated['start_time'],
            $validated['end_time'],
            $validated['day_of_week'] ?? null,
            isset($validated['specific_date']) ? Carbon::parse($validated['specific_date']) : null,
            $validated['location_id'] ?? null
        );

        if (!$hasConflict) {
            return response()->json([
                'success' => false,
                'message' => 'Conflito de horário detectado. Já existe um slot configurado neste período.',
            ], 422);
        }

        // Criar slot
        if ($validated['type'] === AvailabilitySlot::TYPE_RECURRING) {
            $slot = $this->availabilityService->createRecurringSlot(
                $doctor,
                $validated['day_of_week'],
                $validated['start_time'],
                $validated['end_time'],
                $validated['location_id'] ?? null
            );
        } else {
            $slot = $this->availabilityService->createSpecificSlot(
                $doctor,
                $validated['specific_date'],
                $validated['start_time'],
                $validated['end_time'],
                $validated['location_id'] ?? null
            );
        }

        $slot->load('location');

        return response()->json([
            'success' => true,
            'message' => 'Slot de disponibilidade criado com sucesso.',
            'data' => [
                'id' => $slot->id,
                'type' => $slot->type,
                'day_of_week' => $slot->day_of_week,
                'day_of_week_label' => $slot->day_of_week_label,
                'specific_date' => $slot->specific_date?->format('Y-m-d'),
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'location_id' => $slot->location_id,
                'location' => $slot->location ? [
                    'id' => $slot->location->id,
                    'name' => $slot->location->name,
                    'type' => $slot->location->type,
                ] : null,
                'is_active' => $slot->is_active,
            ],
        ], 201);
    }

    /**
     * Atualizar slot de disponibilidade
     * PUT /api/doctors/{doctor}/availability/{slot}
     */
    public function update(UpdateAvailabilitySlotRequest $request, Doctor $doctor, AvailabilitySlot $slot): JsonResponse
    {
        // Autorização: médico só pode atualizar seus próprios slots
        if (auth()->user()->doctor->id !== $doctor->id || $slot->doctor_id !== $doctor->id) {
            abort(403, 'Você não tem permissão para atualizar este slot.');
        }

        $validated = $request->validated();

        // Validar conflitos de horários (excluindo o próprio slot)
        if (isset($validated['start_time']) || isset($validated['end_time'])) {
            $startTime = $validated['start_time'] ?? $slot->start_time;
            $endTime = $validated['end_time'] ?? $slot->end_time;
            $dayOfWeek = $validated['day_of_week'] ?? $slot->day_of_week;
            $specificDate = isset($validated['specific_date']) 
                ? Carbon::parse($validated['specific_date']) 
                : $slot->specific_date;
            $locationId = $validated['location_id'] ?? $slot->location_id;

            // Validar duração mínima (config: telemedicine.availability.slot_min_duration_minutes)
            $start = Carbon::createFromFormat('H:i:s', $startTime . ':00');
            $end = Carbon::createFromFormat('H:i:s', $endTime . ':00');
            $diffInMinutes = $start->diffInMinutes($end);
            $minMinutes = (int) config('telemedicine.availability.slot_min_duration_minutes', 60);

            if ($diffInMinutes < $minMinutes) {
                return response()->json([
                    'success' => false,
                    'message' => 'O horário de fim deve ser pelo menos 1 hora após o horário de início.',
                ], 422);
            }

            $hasConflict = $this->scheduleService->validateSlotConflicts(
                $doctor,
                $startTime,
                $endTime,
                $dayOfWeek,
                $specificDate,
                $locationId,
                $slot->id // Excluir o próprio slot
            );

            if (!$hasConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conflito de horário detectado. Já existe outro slot configurado neste período.',
                ], 422);
            }
        }

        $slot->update($validated);
        $slot->load('location');

        return response()->json([
            'success' => true,
            'message' => 'Slot de disponibilidade atualizado com sucesso.',
            'data' => [
                'id' => $slot->id,
                'type' => $slot->type,
                'day_of_week' => $slot->day_of_week,
                'day_of_week_label' => $slot->day_of_week_label,
                'specific_date' => $slot->specific_date?->format('Y-m-d'),
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'location_id' => $slot->location_id,
                'location' => $slot->location ? [
                    'id' => $slot->location->id,
                    'name' => $slot->location->name,
                    'type' => $slot->location->type,
                ] : null,
                'is_active' => $slot->is_active,
            ],
        ], 200);
    }

    /**
     * Deletar slot de disponibilidade
     * DELETE /api/doctors/{doctor}/availability/{slot}
     */
    public function destroy(Doctor $doctor, AvailabilitySlot $slot): JsonResponse
    {
        // Autorização: médico só pode deletar seus próprios slots
        if (auth()->user()->doctor->id !== $doctor->id || $slot->doctor_id !== $doctor->id) {
            abort(403, 'Você não tem permissão para deletar este slot.');
        }

        $slot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Slot de disponibilidade deletado com sucesso.',
        ], 200);
    }

    /**
     * Obter disponibilidade por data específica
     * GET /api/doctors/{doctor}/availability/{date}
     */
    public function getByDate(Request $request, Doctor $doctor, string $date): JsonResponse
    {
        try {
            $parsedDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data inválida.',
            ], 422);
        }

        $availability = $this->scheduleService->getAvailabilityForDate($doctor, $parsedDate);

        return response()->json([
            'success' => true,
            'data' => $availability,
        ], 200);
    }
}

