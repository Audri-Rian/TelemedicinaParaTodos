<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreBlockedDateRequest;
use App\Models\Doctor;
use App\Models\Doctor\BlockedDate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DoctorBlockedDateController extends Controller
{
    /**
     * Criar nova data bloqueada
     * POST /api/doctors/{doctor}/blocked-dates
     */
    public function store(StoreBlockedDateRequest $request, Doctor $doctor): JsonResponse
    {
        // Autorização
        if (auth()->user()->doctor->id !== $doctor->id) {
            abort(403, 'Você não tem permissão para criar datas bloqueadas para este médico.');
        }

        $validated = $request->validated();

        // Verificar se já existe bloqueio para esta data
        $existingBlock = $doctor->blockedDates()
            ->where('blocked_date', $validated['blocked_date'])
            ->exists();

        if ($existingBlock) {
            return response()->json([
                'success' => false,
                'message' => 'Esta data já está bloqueada.',
            ], 422);
        }

        $blockedDate = BlockedDate::create([
            'doctor_id' => $doctor->id,
            'blocked_date' => $validated['blocked_date'],
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data bloqueada com sucesso.',
            'data' => [
                'id' => $blockedDate->id,
                'blocked_date' => $blockedDate->blocked_date->format('Y-m-d'),
                'formatted_date' => $blockedDate->blocked_date->format('d/m/Y'),
                'reason' => $blockedDate->reason,
            ],
        ], 201);
    }

    /**
     * Deletar data bloqueada
     * DELETE /api/doctors/{doctor}/blocked-dates/{blockedDate}
     */
    public function destroy(Doctor $doctor, BlockedDate $blockedDate): JsonResponse
    {
        // Autorização: médico só pode deletar suas próprias datas bloqueadas
        if (auth()->user()->doctor->id !== $doctor->id || $blockedDate->doctor_id !== $doctor->id) {
            abort(403, 'Você não tem permissão para deletar este bloqueio.');
        }

        $blockedDate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data bloqueada removida com sucesso.',
        ], 200);
    }
}

