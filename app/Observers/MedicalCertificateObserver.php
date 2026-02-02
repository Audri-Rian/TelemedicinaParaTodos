<?php

namespace App\Observers;

use App\Events\MedicalCertificateIssued;
use App\MedicalRecord\Infrastructure\Persistence\Models\MedicalCertificate;

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
