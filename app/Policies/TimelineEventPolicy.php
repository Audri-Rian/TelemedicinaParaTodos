<?php

namespace App\Policies;

use App\Models\TimelineEvent;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TimelineEventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Usuários autenticados podem ver seus próprios eventos
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TimelineEvent $timelineEvent): bool
    {
        // Usuário pode ver seus próprios eventos
        return $timelineEvent->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Qualquer usuário autenticado pode criar eventos
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TimelineEvent $timelineEvent): bool
    {
        // Usuário pode atualizar seus próprios eventos
        return $timelineEvent->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TimelineEvent $timelineEvent): bool
    {
        // Usuário pode deletar seus próprios eventos
        return $timelineEvent->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TimelineEvent $timelineEvent): bool
    {
        // Por enquanto, apenas o próprio usuário pode restaurar
        // Pode ser expandido para admin no futuro
        return $timelineEvent->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TimelineEvent $timelineEvent): bool
    {
        // Por enquanto, apenas o próprio usuário pode deletar permanentemente
        // Pode ser expandido para admin no futuro
        return $timelineEvent->user_id === $user->id;
    }
}
