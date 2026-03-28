<?php

namespace App\Integrations\Http\Controllers;

use App\Integrations\Services\CircuitBreaker;
use App\Models\IntegrationEvent;
use App\Models\IntegrationQueueItem;
use App\Models\PartnerIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Métricas operacionais da camada de integração.
 *
 * Expõe dados para dashboard de monitoramento (LGTM stack ou similar).
 */
class IntegrationMetricsController
{
    public function __construct(
        private readonly CircuitBreaker $circuitBreaker,
    ) {}

    public function index(): JsonResponse
    {
        $partners = PartnerIntegration::all();

        // Query agregada para evitar N+1 — 1 query para todos os parceiros
        $eventsAggregated = IntegrationEvent::where('created_at', '>=', now()->subDay())
            ->select([
                'partner_integration_id',
                DB::raw("COUNT(*) as total"),
                DB::raw("COUNT(CASE WHEN status = 'success' THEN 1 END) as success"),
                DB::raw("COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed"),
                DB::raw("COUNT(CASE WHEN direction = 'outbound' THEN 1 END) as outbound"),
                DB::raw("COUNT(CASE WHEN direction = 'inbound' THEN 1 END) as inbound"),
                DB::raw("AVG(duration_ms) as avg_duration_ms"),
            ])
            ->groupBy('partner_integration_id')
            ->get()
            ->keyBy('partner_integration_id');

        // Métricas por parceiro (CB state lido 1x e reutilizado)
        $cbOpenCount = 0;
        $partnerMetrics = $partners->map(function (PartnerIntegration $partner) use ($eventsAggregated, &$cbOpenCount) {
            $cbState = $this->circuitBreaker->getState($partner->id);
            if ($cbState === 'open') {
                $cbOpenCount++;
            }

            $events = $eventsAggregated->get($partner->id);

            return [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name,
                'partner_type' => $partner->type,
                'status' => $partner->status,
                'circuit_breaker_state' => $cbState,
                'last_sync_at' => $partner->last_sync_at?->toIso8601String(),
                'events_24h' => [
                    'total' => $events->total ?? 0,
                    'success' => $events->success ?? 0,
                    'failed' => $events->failed ?? 0,
                    'outbound' => $events->outbound ?? 0,
                    'inbound' => $events->inbound ?? 0,
                    'avg_duration_ms' => $events?->avg_duration_ms ? round($events->avg_duration_ms) : null,
                    'success_rate' => ($events->total ?? 0) > 0
                        ? round(($events->success / $events->total) * 100, 1)
                        : null,
                ],
            ];
        });

        // Métricas da fila — 1 query consolidada
        $queueMetrics = IntegrationQueueItem::select([
            DB::raw("SUM(CASE WHEN status = '" . IntegrationQueueItem::STATUS_QUEUED . "' THEN 1 ELSE 0 END) as pending"),
            DB::raw("SUM(CASE WHEN status = '" . IntegrationQueueItem::STATUS_PROCESSING . "' THEN 1 ELSE 0 END) as processing"),
            DB::raw("SUM(CASE WHEN status = '" . IntegrationQueueItem::STATUS_FAILED . "' THEN 1 ELSE 0 END) as failed"),
        ])->first();

        // Health check do Redis
        $redisHealthy = false;
        try {
            Redis::ping();
            $redisHealthy = true;
        } catch (\Throwable $e) {
            Log::channel('integration')->error('Redis health check failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'global' => [
                'active_partners' => $partners->where('status', PartnerIntegration::STATUS_ACTIVE)->count(),
                'total_partners' => $partners->count(),
                'circuit_breakers_open' => $cbOpenCount,
            ],
            'queue' => [
                'pending' => (int) ($queueMetrics->pending ?? 0),
                'processing' => (int) ($queueMetrics->processing ?? 0),
                'failed' => (int) ($queueMetrics->failed ?? 0),
            ],
            'partners' => $partnerMetrics,
            'infrastructure' => [
                'redis' => $redisHealthy ? 'ok' : 'error',
            ],
        ]);
    }
}
