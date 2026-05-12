<?php

namespace App\Integrations\Rnds\Services;

use App\Models\PartnerIntegration;
use Illuminate\Support\Facades\Cache;

class RndsPartnerRegistrar
{
    private const CACHE_KEY = 'partner:rnds-datasus';

    public function ensurePartner(): PartnerIntegration
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(24), fn () => PartnerIntegration::firstOrCreate(
            ['slug' => 'rnds-datasus'],
            [
                'name' => 'RNDS (DATASUS)',
                'type' => PartnerIntegration::TYPE_RNDS,
                'status' => PartnerIntegration::STATUS_ACTIVE,
                'base_url' => config('integrations.rnds.base_url'),
                'capabilities' => ['submit_bundle'],
                'fhir_version' => 'R4',
            ],
        )
        );
    }
}
