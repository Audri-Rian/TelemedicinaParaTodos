<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimelineEventRequest;
use App\Http\Requests\UpdateTimelineEventRequest;
use App\Models\TimelineEvent;
use App\Services\TimelineEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class TimelineEventController extends Controller
{
    public function __construct(
        protected TimelineEventService $timelineEventService
    ) {}

    /**
     * Display a listing of the resource.
     * GET /api/timeline-events
     */
    #[OA\PathItem(path: '/api/timeline-events')]
    #[OA\Get(path: '/api/timeline-events', summary: 'Listar eventos de timeline', tags: ['Timeline'], parameters: [new OA\Parameter(name: 'type', in: 'query', required: false)], responses: [new OA\Response(response: 200, description: 'Lista de eventos'), new OA\Response(response: 401, description: 'Não autenticado')])]
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $type = $request->query('type'); // Filtro opcional por tipo

        Gate::authorize('viewAny', TimelineEvent::class);

        $events = $this->timelineEventService->getTimelineForUser($user, $type);
        $formattedEvents = $this->timelineEventService->formatTimelineForDisplay($events);

        return response()->json([
            'success' => true,
            'data' => $formattedEvents,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/timeline-events
     */
    #[OA\PathItem(path: '/api/timeline-events')]
    #[OA\Post(path: '/api/timeline-events', summary: 'Criar evento de timeline', tags: ['Timeline'], responses: [new OA\Response(response: 201, description: 'Evento criado'), new OA\Response(response: 401, description: 'Não autenticado'), new OA\Response(response: 422, description: 'Dados inválidos')])]
    public function store(StoreTimelineEventRequest $request): JsonResponse
    {
        $user = Auth::user();

        Gate::authorize('create', TimelineEvent::class);

        try {
            $event = $this->timelineEventService->createEvent($user, $request->validated());
            $formattedEvent = $this->timelineEventService->formatTimelineForDisplay(collect([$event]));

            return response()->json([
                'success' => true,
                'message' => 'Evento de timeline criado com sucesso.',
                'data' => $formattedEvent[0],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar evento de timeline: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/timeline-events/{id}
     */
    #[OA\PathItem(path: '/api/timeline-events/{timelineEvent}')]
    #[OA\Get(path: '/api/timeline-events/{timelineEvent}', summary: 'Exibir evento', tags: ['Timeline'], parameters: [new OA\PathParameter(name: 'timelineEvent', description: 'ID do evento')], responses: [new OA\Response(response: 200, description: 'Evento'), new OA\Response(response: 401, description: 'Não autenticado'), new OA\Response(response: 404, description: 'Não encontrado')])]
    public function show(TimelineEvent $timelineEvent): JsonResponse
    {
        Gate::authorize('view', $timelineEvent);

        $formattedEvent = $this->timelineEventService->formatTimelineForDisplay(collect([$timelineEvent]));

        return response()->json([
            'success' => true,
            'data' => $formattedEvent[0],
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/timeline-events/{id}
     */
    #[OA\PathItem(path: '/api/timeline-events/{timelineEvent}')]
    #[OA\Put(path: '/api/timeline-events/{timelineEvent}', summary: 'Atualizar evento', tags: ['Timeline'], parameters: [new OA\PathParameter(name: 'timelineEvent', description: 'ID do evento')], responses: [new OA\Response(response: 200, description: 'Evento atualizado'), new OA\Response(response: 401, description: 'Não autenticado'), new OA\Response(response: 422, description: 'Dados inválidos')])]
    public function update(UpdateTimelineEventRequest $request, TimelineEvent $timelineEvent): JsonResponse
    {
        Gate::authorize('update', $timelineEvent);

        try {
            $event = $this->timelineEventService->updateEvent($timelineEvent, $request->validated());
            $formattedEvent = $this->timelineEventService->formatTimelineForDisplay(collect([$event]));

            return response()->json([
                'success' => true,
                'message' => 'Evento de timeline atualizado com sucesso.',
                'data' => $formattedEvent[0],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar evento de timeline: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/timeline-events/{id}
     */
    #[OA\PathItem(path: '/api/timeline-events/{timelineEvent}')]
    #[OA\Delete(path: '/api/timeline-events/{timelineEvent}', summary: 'Excluir evento', tags: ['Timeline'], parameters: [new OA\PathParameter(name: 'timelineEvent', description: 'ID do evento')], responses: [new OA\Response(response: 200, description: 'Evento excluído'), new OA\Response(response: 401, description: 'Não autenticado')])]
    public function destroy(TimelineEvent $timelineEvent): JsonResponse
    {
        Gate::authorize('delete', $timelineEvent);

        try {
            $this->timelineEventService->deleteEvent($timelineEvent);

            return response()->json([
                'success' => true,
                'message' => 'Evento de timeline deletado com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar evento de timeline: ' . $e->getMessage(),
            ], 422);
        }
    }
}
