<?php

namespace App\Events;

use App\MedicalRecord\Infrastructure\Persistence\Models\Examination;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExaminationRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public Examination $examination)
    {
        $this->examination->loadMissing(['doctor.user', 'patient.user', 'appointment']);
    }
}
