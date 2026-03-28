<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreScheduleConfigRequest;
use App\Models\Doctor;
use App\Services\Doctor\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DoctorScheduleController extends Controller
{
    public function __construct(
        protected ScheduleService $scheduleService
    ) {}

    /**
     * Carregar configuração completa da agenda do médico
     * GET /api/doctors/{doctor}/schedule
     */
    public function show(Doctor $doctor): JsonResponse|Response
    {
        Gate::authorize('manageDoctorSchedule', $doctor);

        $config = $this->scheduleService->getScheduleConfig($doctor);

        // Se for requisição Inertia (página web)
        if (request()->expectsJson() && !request()->wantsJson()) {
            return Inertia::render('Doctor/ScheduleManagement', [
                'scheduleConfig' => $config,
            ]);
        }

        // Retornar JSON para API
        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }

    /**
     * Salvar configuração completa da agenda (batch)
     * POST /api/doctors/{doctor}/schedule/save
     */
    public function save(StoreScheduleConfigRequest $request, Doctor $doctor): JsonResponse
    {
        Gate::authorize('manageDoctorSchedule', $doctor);

        try {
            $config = $this->scheduleService->saveScheduleConfig($doctor, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Configuração salva com sucesso.',
                'data' => $config,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar configuração: ' . $e->getMessage(),
            ], 422);
        }
    }
}

