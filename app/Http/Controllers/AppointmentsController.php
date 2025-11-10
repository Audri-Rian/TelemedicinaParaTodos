<?php

namespace App\Http\Controllers;

use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointments;
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
}
