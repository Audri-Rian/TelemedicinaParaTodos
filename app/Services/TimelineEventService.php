<?php

namespace App\Services;

use App\Models\TimelineEvent;
use App\Models\User;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TimelineEventService
{
    /**
     * Obter timeline ordenada de um usuário
     */
    public function getTimelineForUser(User $user, ?string $type = null): Collection
    {
        $query = TimelineEvent::where('user_id', $user->id)
            ->ordered();

        if ($type && TimelineEvent::isValidType($type)) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    /**
     * Criar evento de timeline
     */
    public function createEvent(User $user, array $data): TimelineEvent
    {
        $event = TimelineEvent::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'description' => $data['description'] ?? null,
            'media_url' => $data['media_url'] ?? null,
            'degree_type' => $data['degree_type'] ?? null,
            'is_public' => $data['is_public'] ?? true,
            'extra_data' => $data['extra_data'] ?? null,
            'order_priority' => $data['order_priority'] ?? 0,
        ]);

        // Marcar timeline como completada se o usuário ainda não marcou
        if (!$user->timeline_completed) {
            $user->update(['timeline_completed' => true]);
        }

        return $event;
    }

    /**
     * Atualizar evento de timeline
     */
    public function updateEvent(TimelineEvent $event, array $data): TimelineEvent
    {
        $event->update([
            'type' => $data['type'] ?? $event->type,
            'title' => $data['title'] ?? $event->title,
            'subtitle' => $data['subtitle'] ?? $event->subtitle,
            'start_date' => $data['start_date'] ?? $event->start_date,
            'end_date' => $data['end_date'] ?? $event->end_date,
            'description' => $data['description'] ?? $event->description,
            'media_url' => $data['media_url'] ?? $event->media_url,
            'degree_type' => $data['degree_type'] ?? $event->degree_type,
            'is_public' => isset($data['is_public']) ? $data['is_public'] : $event->is_public,
            'extra_data' => $data['extra_data'] ?? $event->extra_data,
            'order_priority' => $data['order_priority'] ?? $event->order_priority,
        ]);

        return $event->fresh();
    }

    /**
     * Deletar evento de timeline
     */
    public function deleteEvent(TimelineEvent $event): bool
    {
        return $event->delete();
    }

    /**
     * Formatar timeline para exibição
     */
    public function formatTimelineForDisplay(Collection $events): array
    {
        return $events->map(function ($event) {
            return [
                'id' => $event->id,
                'type' => $event->type,
                'type_label' => $event->type_label,
                'title' => $event->title,
                'subtitle' => $event->subtitle,
                'start_date' => $event->start_date ? $event->start_date->format('Y-m-d') : null,
                'end_date' => $event->end_date ? $event->end_date->format('Y-m-d') : null,
                'formatted_start_date' => $event->formatted_start_date,
                'formatted_end_date' => $event->formatted_end_date,
                'date_range' => $event->date_range,
                'duration' => $event->duration,
                'description' => $event->description,
                'media_url' => $event->media_url,
                'extra_data' => $event->extra_data ?? [],
                'order_priority' => $event->order_priority,
                'is_in_progress' => $event->is_in_progress,
                'created_at' => $event->created_at ? $event->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $event->updated_at ? $event->updated_at->format('Y-m-d H:i:s') : null,
            ];
        })->values()->toArray();
    }

    /**
     * Filtrar eventos por tipo
     */
    public function filterByType(Collection $events, string $type): Collection
    {
        if (!TimelineEvent::isValidType($type)) {
            return collect([]);
        }

        return $events->where('type', $type);
    }

    /**
     * Obter eventos em andamento
     */
    public function getInProgressEvents(User $user): Collection
    {
        return TimelineEvent::where('user_id', $user->id)
            ->whereNull('end_date')
            ->ordered()
            ->get();
    }

    /**
     * Obter eventos concluídos
     */
    public function getCompletedEvents(User $user): Collection
    {
        return TimelineEvent::where('user_id', $user->id)
            ->whereNotNull('end_date')
            ->ordered()
            ->get();
    }
}

