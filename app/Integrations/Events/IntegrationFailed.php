<?php

namespace App\Integrations\Events;

use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IntegrationFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PartnerIntegration $partner,
        public readonly IntegrationEvent $event,
        public readonly string $errorMessage,
    ) {}
}
