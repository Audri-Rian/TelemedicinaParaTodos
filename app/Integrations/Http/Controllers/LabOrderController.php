<?php

namespace App\Integrations\Http\Controllers;

use App\Models\Examination;
use App\Models\PartnerIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint público para laboratórios consultarem pedidos pendentes.
 *
 * O laboratório faz GET para buscar pedidos que foram enviados para ele
 * mas ainda não tiveram resultado registrado.
 */
class LabOrderController
{
    private const MAX_LIMIT = 100;

    /**
     * Lista pedidos de exame pendentes para o laboratório.
     *
     * GET /api/v1/public/lab/{partnerSlug}/orders
     */
    public function index(Request $request, string $partnerSlug): JsonResponse
    {
        $partner = PartnerIntegration::where('slug', $partnerSlug)
            ->where('type', PartnerIntegration::TYPE_LABORATORY)
            ->first();

        if (! $partner) {
            return response()->json([
                'error' => 'Laboratório não encontrado.',
            ], 404);
        }

        if (! $partner->isActive()) {
            return response()->json([
                'error' => 'Integração inativa.',
            ], 403);
        }

        $limit = min($request->integer('limit', 50), self::MAX_LIMIT);

        $pendingExams = Examination::where('partner_integration_id', $partner->id)
            ->whereIn('status', [Examination::STATUS_REQUESTED, Examination::STATUS_IN_PROGRESS])
            ->whereNotNull('external_id')
            ->with(['patient.user', 'doctor.user'])
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $entries = $pendingExams->map(function (Examination $exam) {
            return [
                'resource' => [
                    'resourceType' => 'ServiceRequest',
                    'id' => $exam->external_id,
                    'status' => 'active',
                    'intent' => 'order',
                    'subject' => [
                        'reference' => "Patient/{$exam->patient_id}",
                        'display' => $exam->patient?->user?->name ?? 'Paciente',
                    ],
                    'requester' => [
                        'reference' => "Practitioner/{$exam->doctor_id}",
                        'display' => $exam->doctor?->user?->name ?? 'Médico',
                    ],
                    'code' => [
                        'text' => $exam->name,
                    ],
                    'authoredOn' => $exam->created_at->toIso8601String(),
                ],
            ];
        });

        Log::channel('integration')->info('Lab consultou pedidos pendentes', [
            'partner_id' => $partner->id,
            'partner_slug' => $partnerSlug,
            'count' => $entries->count(),
        ]);

        return response()->json([
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => $entries->count(),
            'entry' => $entries->values(),
        ]);
    }
}
