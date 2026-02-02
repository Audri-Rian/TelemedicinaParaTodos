<?php

namespace App\Events;

use App\MedicalRecord\Infrastructure\Persistence\Models\Prescription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrescriptionIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(public Prescription $prescription)
    {
        $this->prescription->loadMissing(['doctor.user', 'patient.user', 'appointment']);
    }
}
