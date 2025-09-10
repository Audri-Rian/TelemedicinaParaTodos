<?php

namespace App\Http\Controllers;

use App\Models\Specialization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpecializationController extends Controller
{
    /**
     * Display a listing of all specializations.
     */
    public function index(): Response
    {
        $specializations = Specialization::withCount('doctors')
            ->orderBy('name')
            ->get();

        return Inertia::render('Specializations/Index', [
            'specializations' => $specializations
        ]);
    }

    /**
     * Get specializations for API/AJAX requests.
     */
    public function list(Request $request): JsonResponse
    {
        $query = Specialization::query();

        // Filtro por nome (busca)
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Apenas especializações com médicos ativos
        if ($request->boolean('active_only')) {
            $query->whereHas('doctors', function ($doctorQuery) {
                $doctorQuery->where('status', 'active');
            });
        }

        // Incluir contagem de médicos
        if ($request->boolean('with_count')) {
            $query->withCount(['doctors', 'doctors as active_doctors_count' => function ($doctorQuery) {
                $doctorQuery->where('status', 'active');
            }]);
        }

        $specializations = $query->orderBy('name')->get();

        return response()->json([
            'data' => $specializations
        ]);
    }

    /**
     * Get specializations for select/dropdown components.
     */
    public function options(): JsonResponse
    {
        $specializations = Specialization::orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($specialization) {
                return [
                    'value' => $specialization->id,
                    'label' => $specialization->name
                ];
            });

        return response()->json([
            'data' => $specializations
        ]);
    }

    /**
     * Show the form for creating a new specialization.
     */
    public function create(): Response
    {
        return Inertia::render('Specializations/Create');
    }

    /**
     * Store a newly created specialization.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:specializations,name'
        ], [
            'name.required' => 'O nome da especialização é obrigatório.',
            'name.unique' => 'Uma especialização com este nome já existe.',
            'name.max' => 'O nome não pode ter mais de 100 caracteres.'
        ]);

        try {
            $specialization = Specialization::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Especialização criada com sucesso.',
                'data' => $specialization
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Erro ao criar especialização: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Display the specified specialization.
     */
    public function show(Specialization $specialization): Response
    {
        $specialization->load(['doctors' => function ($query) {
            $query->with('user:id,name,email')
                  ->where('status', 'active')
                  ->orderBy('created_at', 'desc');
        }]);

        return Inertia::render('Specializations/Show', [
            'specialization' => $specialization
        ]);
    }

    /**
     * Show the form for editing the specified specialization.
     */
    public function edit(Specialization $specialization): Response
    {
        return Inertia::render('Specializations/Edit', [
            'specialization' => $specialization
        ]);
    }

    /**
     * Update the specified specialization.
     */
    public function update(Request $request, Specialization $specialization): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:specializations,name,' . $specialization->id
        ], [
            'name.required' => 'O nome da especialização é obrigatório.',
            'name.unique' => 'Uma especialização com este nome já existe.',
            'name.max' => 'O nome não pode ter mais de 100 caracteres.'
        ]);

        try {
            $specialization->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Especialização atualizada com sucesso.',
                'data' => $specialization
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar especialização: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Remove the specified specialization.
     */
    public function destroy(Specialization $specialization): JsonResponse
    {
        try {
            // Verificar se há médicos associados
            if ($specialization->doctors()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível excluir uma especialização que possui médicos associados.'
                ], 422);
            }

            $specialization->delete();

            return response()->json([
                'success' => true,
                'message' => 'Especialização excluída com sucesso.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao excluir especialização: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente.'
            ], 500);
        }
    }
}
