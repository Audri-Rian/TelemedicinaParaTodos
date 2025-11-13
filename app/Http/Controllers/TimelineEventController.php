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

class TimelineEventController extends Controller
{
    public function __construct(
        protected TimelineEventService $timelineEventService
    ) {}

    /**
     * Display a listing of the resource.
     * GET /api/timeline-events
     */
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
