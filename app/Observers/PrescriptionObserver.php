<?php

namespace App\Observers;

use App\Events\PrescriptionIssued;
use App\Models\Prescription;
use Illuminate\Validation\ValidationException;

class PrescriptionObserver
{
    /**
     * Handle the Prescription "created" event.
     */
    public function created(Prescription $prescription): void
    {
        // Disparar evento de notificação
        event(new PrescriptionIssued($prescription->fresh()));
    }

    /**
     * Handle the Prescription "updated" event.
     */
    public function updated(Prescription $prescription): void
    {
        //
    }

    public function updating(Prescription $prescription): void
    {
        if (! $prescription->isSigned()) {
            return;
        }

        $lockedFields = [
            'medications',
            'instructions',
            'valid_until',
            'issued_at',
            'doctor_id',
            'patient_id',
            'appointment_id',
        ];

        $dirtyLockedFields = array_values(array_filter(
            $lockedFields,
            fn (string $field): bool => $prescription->isDirty($field),
        ));

        if ($dirtyLockedFields === []) {
            return;
        }

        throw ValidationException::withMessages([
            'prescription' => 'Prescrição assinada não pode ter conteúdo alterado.',
        ]);
    }

    /**
     * Handle the Prescription "deleted" event.
     */
    public function deleted(Prescription $prescription): void
    {
        //
    }

    /**
     * Handle the Prescription "restored" event.
     */
    public function restored(Prescription $prescription): void
    {
        //
    }

    /**
     * Handle the Prescription "force deleted" event.
     */
    public function forceDeleted(Prescription $prescription): void
    {
        //
    }
}
