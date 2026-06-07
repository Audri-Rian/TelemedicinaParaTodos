<?php

namespace App\Observers;

use App\Events\MedicalCertificateIssued;
use App\Models\MedicalCertificate;
use Illuminate\Validation\ValidationException;

class MedicalCertificateObserver
{
    /**
     * Handle the MedicalCertificate "created" event.
     */
    public function created(MedicalCertificate $medicalCertificate): void
    {
        // Disparar evento de notificação
        event(new MedicalCertificateIssued($medicalCertificate->fresh()));
    }

    /**
     * Handle the MedicalCertificate "updated" event.
     */
    public function updated(MedicalCertificate $medicalCertificate): void
    {
        //
    }

    public function updating(MedicalCertificate $medicalCertificate): void
    {
        if (! $medicalCertificate->isSigned()) {
            return;
        }

        $lockedFields = [
            'type',
            'start_date',
            'end_date',
            'days',
            'reason',
            'restrictions',
            'doctor_id',
            'patient_id',
            'appointment_id',
            'crm_number',
        ];

        $dirtyLockedFields = array_values(array_filter(
            $lockedFields,
            fn (string $field): bool => $medicalCertificate->isDirty($field),
        ));

        if ($dirtyLockedFields === []) {
            return;
        }

        throw ValidationException::withMessages([
            'medical_certificate' => 'Atestado assinado não pode ter conteúdo alterado.',
        ]);
    }

    /**
     * Handle the MedicalCertificate "deleted" event.
     */
    public function deleted(MedicalCertificate $medicalCertificate): void
    {
        //
    }

    /**
     * Handle the MedicalCertificate "restored" event.
     */
    public function restored(MedicalCertificate $medicalCertificate): void
    {
        //
    }

    /**
     * Handle the MedicalCertificate "force deleted" event.
     */
    public function forceDeleted(MedicalCertificate $medicalCertificate): void
    {
        //
    }
}
