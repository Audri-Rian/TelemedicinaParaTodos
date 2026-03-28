<?php

namespace App\Integrations\Http\Controllers;

use App\Models\PartnerIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PartnerHealthController extends Controller
{
    /**
     * Verifica status de integração de um parceiro.
     *
     * GET /api/v1/public/health/{partnerSlug}
     */
    public function check(string $partnerSlug): JsonResponse
    {
        $partner = PartnerIntegration::where('slug', $partnerSlug)->first();

        if (! $partner) {
            return response()->json(['error' => 'partner_not_found'], 404);
        }

        $lastEvent = $partner->events()->latest()->first();

        return response()->json([
            'status' => $partner->isActive() ? 'ok' : $partner->status,
            'partner' => $partner->slug,
            'type' => $partner->type,
            'capabilities' => $partner->capabilities,
            'last_event' => $lastEvent?->created_at?->toIso8601String(),
            'last_sync' => $partner->last_sync_at?->toIso8601String(),
        ]);
    }
}
