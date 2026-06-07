<?php

namespace App\Integrations\Events;

use App\Models\Examination;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamOrderSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Examination $examination,
        public PartnerIntegration $partner,
        public string $externalId,
    ) {}
}
