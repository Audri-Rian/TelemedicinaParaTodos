<?php

namespace App\Integrations\Http\Middleware;

use App\Models\IntegrationWebhook;
use App\Models\PartnerIntegration;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida assinatura HMAC-SHA256 de webhooks recebidos.
 *
 * Headers esperados:
 * - X-Webhook-Signature: sha256={hmac}
 * - X-Webhook-Timestamp: {unix_timestamp}
 */
class ValidateWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signatureHeader = config('integrations.webhook.signature_header');
        $timestampHeader = config('integrations.webhook.timestamp_header');
        $tolerance = config('integrations.webhook.timestamp_tolerance_seconds');

        $signature = $request->header($signatureHeader);
        $timestamp = $request->header($timestampHeader);

        // Se não houver headers de assinatura, pular validação (parceiro pode não suportar)
        if (! $signature && ! $timestamp) {
            return $next($request);
        }

        // Se apenas um dos headers estiver presente, rejeitar
        if (($signature && ! $timestamp) || (! $signature && $timestamp)) {
            return response()->json([
                'error' => 'webhook_missing_signature_or_timestamp',
                'message' => 'Both signature and timestamp headers are required',
            ], 401);
        }

        // Validar timestamp (anti-replay)
        if ($timestamp) {
            $age = abs(time() - (int) $timestamp);
            if ($age > $tolerance) {
                return response()->json([
                    'error' => 'webhook_timestamp_expired',
                    'message' => 'Webhook timestamp is too old',
                ], 401);
            }
        }

        // Buscar secret do parceiro
        $partnerSlug = $request->route('partnerSlug');
        $partner = PartnerIntegration::with([
            'webhooks' => fn ($q) => $q->where('status', IntegrationWebhook::STATUS_ACTIVE),
        ])->where('slug', $partnerSlug)->first();

        if (! $partner) {
            return response()->json(['error' => 'partner_not_found'], 404);
        }

        $webhook = $partner->webhooks->first();

        if (! $webhook || ! $webhook->secret) {
            // Sem webhook/secret configurado — pular validação de assinatura
            Log::channel('integration')->warning('Webhook signature validation skipped: no webhook or secret configured', [
                'partner_slug' => $partnerSlug,
            ]);

            return $next($request);
        }

        // Validar HMAC
        $body = $request->getContent();
        $payload = ($timestamp ? $timestamp . '.' : '') . $body;
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $webhook->secret);

        if (! hash_equals($expectedSignature, $signature ?? '')) {
            Log::channel('integration')->warning('Webhook signature invalid', [
                'event' => $request->route()->getName(),
                'route' => $request->path(),
                'ip' => $request->ip(),
                'received_signature' => substr($signature ?? '', 0, 10) . '...',
                'expected_signature' => substr($expectedSignature, 0, 10) . '...',
            ]);

            return response()->json([
                'error' => 'webhook_signature_invalid',
                'message' => 'Invalid webhook signature',
            ], 401);
        }

        return $next($request);
    }
}
