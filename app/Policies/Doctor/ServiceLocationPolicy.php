<?php

namespace App\Policies\Doctor;

use App\Models\ServiceLocation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceLocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Apenas médicos podem ver seus próprios locais
        return $user->isDoctor();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceLocation $serviceLocation): bool
    {
        // Médico só pode ver seus próprios locais
        if (!$user->isDoctor()) {
            return false;
        }

        return $serviceLocation->doctor_id === $user->doctor->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Apenas médicos podem criar locais
        return $user->isDoctor();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceLocation $serviceLocation): bool
    {
        // Médico só pode atualizar seus próprios locais
        if (!$user->isDoctor()) {
            return false;
        }

        return $serviceLocation->doctor_id === $user->doctor->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceLocation $serviceLocation): bool
    {
        // Médico só pode deletar seus próprios locais
        if (!$user->isDoctor()) {
            return false;
        }

        return $serviceLocation->doctor_id === $user->doctor->id;
    }
}

