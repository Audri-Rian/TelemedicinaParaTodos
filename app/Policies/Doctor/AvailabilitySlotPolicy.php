<?php

namespace App\Policies\Doctor;

use App\Models\AvailabilitySlot;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AvailabilitySlotPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Apenas médicos podem ver seus próprios slots
        return $user->isDoctor();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AvailabilitySlot $availabilitySlot): bool
    {
        // Médico só pode ver seus próprios slots
        if (!$user->isDoctor()) {
            return false;
        }

        return $availabilitySlot->doctor_id === $user->doctor->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Apenas médicos podem criar slots
        return $user->isDoctor();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AvailabilitySlot $availabilitySlot): bool
    {
        // Médico só pode atualizar seus próprios slots
        if (!$user->isDoctor()) {
            return false;
        }

        return $availabilitySlot->doctor_id === $user->doctor->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AvailabilitySlot $availabilitySlot): bool
    {
        // Médico só pode deletar seus próprios slots
        if (!$user->isDoctor()) {
            return false;
        }

        return $availabilitySlot->doctor_id === $user->doctor->id;
    }
}

