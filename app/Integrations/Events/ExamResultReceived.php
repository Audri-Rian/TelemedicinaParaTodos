<?php

namespace App\Integrations\Events;

use App\Models\Examination;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamResultReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Examination $examination,
        public PartnerIntegration $partner,
    ) {
        $this->examination->loadMissing(['doctor.user', 'patient.user']);
    }
}
