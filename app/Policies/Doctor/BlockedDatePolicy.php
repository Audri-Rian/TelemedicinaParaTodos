<?php

namespace App\Policies\Doctor;

use App\Models\Doctor\BlockedDate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BlockedDatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Apenas médicos podem ver suas próprias datas bloqueadas
        return $user->isDoctor();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BlockedDate $blockedDate): bool
    {
        // Médico só pode ver suas próprias datas bloqueadas
        if (!$user->isDoctor()) {
            return false;
        }

        return $blockedDate->doctor_id === $user->doctor->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Apenas médicos podem criar datas bloqueadas
        return $user->isDoctor();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BlockedDate $blockedDate): bool
    {
        // Médico só pode deletar suas próprias datas bloqueadas
        if (!$user->isDoctor()) {
            return false;
        }

        return $blockedDate->doctor_id === $user->doctor->id;
    }
}

