<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreServiceLocationRequest;
use App\Http\Requests\Doctor\UpdateServiceLocationRequest;
use App\Models\Doctor;
use App\Models\ServiceLocation;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DoctorServiceLocationController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Criar novo local de atendimento
     * POST /api/doctors/{doctor}/locations
     */
    public function store(StoreServiceLocationRequest $request, Doctor $doctor): JsonResponse
    {
        Gate::authorize('manageDoctorSchedule', $doctor);
        $this->authorize('create', ServiceLocation::class);

        $validated = $request->validated();

        $location = $this->availabilityService->createServiceLocation(
            $doctor,
            $validated['name'],
            $validated['type'],
            $validated['address'] ?? null,
            $validated['phone'] ?? null,
            $validated['description'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Local de atendimento criado com sucesso.',
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
                'type' => $location->type,
                'type_label' => $location->type_label,
                'address' => $location->address,
                'phone' => $location->phone,
                'description' => $location->description,
                'is_active' => $location->is_active,
            ],
        ], 201);
    }

    /**
     * Atualizar local de atendimento
     * PUT /api/doctors/{doctor}/locations/{location}
     */
    public function update(UpdateServiceLocationRequest $request, Doctor $doctor, ServiceLocation $location): JsonResponse
    {
        $this->authorize('update', $location);

        $location->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Local de atendimento atualizado com sucesso.',
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
                'type' => $location->type,
                'type_label' => $location->type_label,
                'address' => $location->address,
                'phone' => $location->phone,
                'description' => $location->description,
                'is_active' => $location->is_active,
            ],
        ], 200);
    }

    /**
     * Deletar local de atendimento
     * DELETE /api/doctors/{doctor}/locations/{location}
     */
    public function destroy(Doctor $doctor, ServiceLocation $location): JsonResponse
    {
        $this->authorize('delete', $location);

        // Verificar se há slots associados
        $hasSlots = $location->availabilitySlots()->exists();

        if ($hasSlots) {
            // Opção: apenas desativar ao invés de deletar
            $location->update(['is_active' => false]);
            
            return response()->json([
                'success' => true,
                'message' => 'Local desativado com sucesso (possui slots associados).',
            ], 200);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Local de atendimento deletado com sucesso.',
        ], 200);
    }
}

