<?php

namespace App\Events;

use App\MedicalRecord\Infrastructure\Persistence\Models\MedicalCertificate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MedicalCertificateIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(public MedicalCertificate $medicalCertificate)
    {
        $this->medicalCertificate->loadMissing(['doctor.user', 'patient.user', 'appointment']);
    }
}
