<?php

namespace App\Http\Controllers;

use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointments;
use App\Models\Doctor;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentsController extends Controller
{
    public function __construct(
        private AppointmentService $appointmentService
    ) {
    }

    /**
     * Listar appointments com filtros
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Appointments::class);

        $user = $request->user();
        $filters = [];

        // Aplicar filtros baseados no tipo de usuário
        if ($user->isDoctor()) {
            $filters['doctor_id'] = $user->doctor->id;
        } elseif ($user->isPatient()) {
            $filters['patient_id'] = $user->patient->id;
        }

        // Filtros da query string
        if ($request->has('status')) {
            $filters['status'] = $request->get('status');
        }

        if ($request->has('date_from')) {
            $filters['date_from'] = $request->get('date_from');
        }

        if ($request->has('date_to')) {
            $filters['date_to'] = $request->get('date_to');
        }

        if ($request->has('upcoming')) {
            $filters['upcoming'] = $request->boolean('upcoming');
        }

        if ($request->has('past')) {
            $filters['past'] = $request->boolean('past');
        }

        $appointments = $this->appointmentService->list($filters);

        return Inertia::render('Doctor/ScheduleManagement', [
            'appointments' => $appointments,
            'filters' => $filters,
        ]);
    }

    /**
     * Criar novo appointment
     */
    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Appointments::class);

        try {
            $data = $request->validated();
            $appointment = $this->appointmentService->create($data, $request->user());

            $user = $request->user();
            
            // Redirecionar baseado no tipo de usuário
            if ($user->isPatient()) {
                return redirect()
                    ->route('patient.consultation-details', $appointment)
                    ->with('success', 'Agendamento criado com sucesso.');
            } elseif ($user->isDoctor()) {
                return redirect()
                    ->route('appointments.show', $appointment)
                    ->with('success', 'Agendamento criado com sucesso.');
            }
            
            // Fallback para rota genérica
            return redirect()
                ->route('appointments.show', $appointment)
                ->with('success', 'Agendamento criado com sucesso.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Exibir detalhes do appointment
     */
    public function show(Appointments $appointment): Response
    {
        $this->authorize('view', $appointment);

        $appointment->load(['doctor.user', 'patient.user', 'logs.user']);

        return Inertia::render('Appointments/Show', [
            'appointment' => $appointment,
        ]);
    }

    /**
     * Atualizar appointment
     */
    public function update(UpdateAppointmentRequest $request, Appointments $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        try {
            $data = $request->validated();
            $this->appointmentService->update($appointment, $data, $request->user());

            return redirect()
                ->route('appointments.show', $appointment)
                ->with('success', 'Agendamento atualizado com sucesso.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Deletar appointment (soft delete)
     */
    public function destroy(Appointments $appointment): RedirectResponse
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Agendamento excluído com sucesso.');
    }

    /**
     * Iniciar consulta
     */
    public function start(Appointments $appointment): JsonResponse
    {
        $this->authorize('start', $appointment);

        $success = $this->appointmentService->start($appointment, auth()->id());

        if (!$success) {
            return response()->json([
                'message' => 'Não foi possível iniciar a consulta.',
            ], 422);
        }

        return response()->json([
            'message' => 'Consulta iniciada com sucesso.',
            'appointment' => $appointment->fresh(),
        ]);
    }

    /**
     * Finalizar consulta
     */
    public function end(Appointments $appointment): JsonResponse
    {
        $this->authorize('end', $appointment);

        $success = $this->appointmentService->end($appointment, auth()->id());

        if (!$success) {
            return response()->json([
                'message' => 'Não foi possível finalizar a consulta.',
            ], 422);
        }

        return response()->json([
            'message' => 'Consulta finalizada com sucesso.',
            'appointment' => $appointment->fresh(),
        ]);
    }

    /**
     * Cancelar consulta
     */
    public function cancel(Request $request, Appointments $appointment): JsonResponse
    {
        $this->authorize('cancel', $appointment);

        $reason = $request->input('reason');
        $success = $this->appointmentService->cancel($appointment, $reason, auth()->id());

        if (!$success) {
            return response()->json([
                'message' => 'Não foi possível cancelar a consulta.',
            ], 422);
        }

        return response()->json([
            'message' => 'Consulta cancelada com sucesso.',
            'appointment' => $appointment->fresh(),
        ]);
    }

    /**
     * Reagendar consulta
     */
    public function reschedule(RescheduleAppointmentRequest $request, Appointments $appointment): JsonResponse
    {
        $this->authorize('reschedule', $appointment);

        $newDateTime = Carbon::parse($request->validated()['scheduled_at']);
        $success = $this->appointmentService->reschedule($appointment, $newDateTime, auth()->id());

        if (!$success) {
            return response()->json([
                'message' => 'Não foi possível reagendar a consulta. Verifique se não há conflito de horário.',
            ], 422);
        }

        return response()->json([
            'message' => 'Consulta reagendada com sucesso.',
            'appointment' => $appointment->fresh(),
        ]);
    }

    /**
     * Verificar disponibilidade de horários para um médico em uma data específica
     */
    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $doctor = Doctor::findOrFail($validated['doctor_id']);

        if (!$doctor->isActive()) {
            return response()->json([
                'message' => 'Médico não está ativo.',
                'doctor_id' => $doctor->id,
                'date' => $validated['date'],
                'available_slots' => [],
                'schedule' => null,
            ], 422);
        }

        $date = Carbon::parse($validated['date']);
        $now = Carbon::now();
        $dayOfWeek = strtolower($date->format('l'));

        $schedule = $doctor->availability_schedule ?? [];
        $daySchedule = $schedule[$dayOfWeek] ?? null;

        if (empty($daySchedule) || empty($daySchedule['slots'])) {
            return response()->json([
                'doctor_id' => $doctor->id,
                'date' => $date->format('Y-m-d'),
                'available_slots' => [],
                'schedule' => $daySchedule,
            ]);
        }

        $existingAppointments = Appointments::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('scheduled_at', $date->toDateString())
            ->whereIn('status', [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_IN_PROGRESS,
            ])
            ->pluck('scheduled_at')
            ->map(fn (Carbon $scheduledAt) => $scheduledAt->format('H:i'))
            ->all();

        // Filtrar slots ocupados e slots passados (se for hoje)
        $availableSlots = array_filter($daySchedule['slots'], function($slot) use ($existingAppointments, $date, $now) {
            // Remover slots ocupados
            if (in_array($slot, $existingAppointments)) {
                return false;
            }
            
            // Se for hoje, remover apenas slots que já passaram
            if ($date->isToday()) {
                try {
                    $slotDateTime = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $slot);
                    // Mostrar slots que são no futuro (pelo menos 5 minutos à frente)
                    $minAllowedTime = $now->copy()->addMinutes(5);
                    return $slotDateTime->greaterThan($minAllowedTime);
                } catch (\Exception $e) {
                    // Se houver erro ao criar a data, manter o slot
                    return true;
                }
            }
            
            return true;
        });

        return response()->json([
            'doctor_id' => $doctor->id,
            'date' => $date->format('Y-m-d'),
            'available_slots' => array_values($availableSlots),
            'schedule' => $daySchedule,
        ]);
    }
}
