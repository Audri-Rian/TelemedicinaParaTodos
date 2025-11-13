<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreScheduleConfigRequest;
use App\Models\Doctor;
use App\Services\Doctor\ScheduleService;
use Illuminate\Http\JsonResponse;
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
        // Autorização: médico só pode ver sua própria agenda
        if (auth()->user()->doctor->id !== $doctor->id) {
            abort(403, 'Você não tem permissão para acessar esta agenda.');
        }

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
        // Autorização: médico só pode salvar sua própria agenda
        if (auth()->user()->doctor->id !== $doctor->id) {
            abort(403, 'Você não tem permissão para alterar esta agenda.');
        }

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

