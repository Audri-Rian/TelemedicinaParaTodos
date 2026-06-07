<?php

namespace App\Integrations\Http\Controllers;

use App\Integrations\Contracts\LabIntegrationInterface;
use App\Integrations\Events\ExamResultReceived;
use App\Models\Examination;
use App\Models\FhirResourceMapping;
use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Recebe resultado de exame de um laboratório parceiro.
     *
     * POST /api/v1/public/webhooks/lab/{partnerSlug}
     */
    public function labResult(
        Request $request,
        string $partnerSlug,
        LabIntegrationInterface $labAdapter,
    ): JsonResponse {
        $rawPayload = $request->all();

        $partner = PartnerIntegration::where('slug', $partnerSlug)
            ->where('type', PartnerIntegration::TYPE_LABORATORY)
            ->active()
            ->first();

        if (! $partner) {
            return response()->json(['error' => 'partner_not_found'], 404);
        }

        // Verificar idempotência
        $idempotencyKey = $request->header('X-Idempotency-Key')
            ?? $this->extractExternalId($rawPayload);

        if ($idempotencyKey) {
            $alreadyProcessed = IntegrationEvent::where([
                'partner_integration_id' => $partner->id,
                'external_id' => $idempotencyKey,
                'event_type' => IntegrationEvent::EVENT_EXAM_RESULT_RECEIVED,
                'status' => IntegrationEvent::STATUS_SUCCESS,
            ])->exists();

            if ($alreadyProcessed) {
                return response()->json(['status' => 'already_processed'], 200);
            }
        }

        // Registrar evento
        $event = IntegrationEvent::create([
            'partner_integration_id' => $partner->id,
            'direction' => IntegrationEvent::DIRECTION_INBOUND,
            'event_type' => IntegrationEvent::EVENT_EXAM_RESULT_RECEIVED,
            'status' => IntegrationEvent::STATUS_PROCESSING,
            'external_id' => $idempotencyKey,
            'request_payload' => $this->sanitizePayloadForAudit($rawPayload),
        ]);

        try {
            $resultDto = $labAdapter->parseWebhookPayload($rawPayload);

            // Encontrar exame no sistema pelo mapeamento FHIR ou external_id
            $examination = $this->findExamination($resultDto->externalId, $partner->id);

            if (! $examination) {
                $event->update([
                    'status' => IntegrationEvent::STATUS_FAILED,
                    'error_message' => "Exame não encontrado para external_id: {$resultDto->externalId}",
                ]);

                return response()->json(['error' => 'examination_not_found'], 404);
            }

            // Atualizar exame com resultado
            $updateData = [
                'status' => Examination::STATUS_COMPLETED,
                'results' => $resultDto->results,
                'completed_at' => $resultDto->completedAt ? Carbon::parse($resultDto->completedAt) : now(),
                'source' => Examination::SOURCE_INTEGRATION,
                'received_from_partner_at' => now(),
                'external_accession' => $resultDto->accessionNumber,
            ];

            if ($resultDto->attachmentUrl && $this->isSafeAttachmentUrl($resultDto->attachmentUrl)) {
                $updateData['attachment_url'] = $resultDto->attachmentUrl;
            }

            $examination->update($updateData);

            $event->update([
                'status' => IntegrationEvent::STATUS_SUCCESS,
                'doctor_id' => $examination->doctor_id,
                'resource_type' => 'examination',
                'resource_id' => $examination->id,
                'fhir_resource_type' => 'DiagnosticReport',
            ]);

            // Disparar evento de resultado recebido
            ExamResultReceived::dispatch($examination->fresh(), $partner);

            // Atualizar last_sync do parceiro
            $partner->update(['last_sync_at' => now()]);

            return response()->json(['status' => 'processed', 'examination_id' => $examination->id]);

        } catch (\Throwable $e) {
            Log::channel('integration')->error('Erro ao processar webhook de laboratório', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            $event->update([
                'status' => IntegrationEvent::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'processing_failed'], 500);
        }
    }

    private function extractExternalId(array $payload): ?string
    {
        // Tentar extrair ID do DiagnosticReport
        foreach ($payload['entry'] ?? [] as $entry) {
            $resource = $entry['resource'] ?? $entry;
            if (($resource['resourceType'] ?? '') === 'DiagnosticReport') {
                return $resource['id'] ?? $resource['identifier'][0]['value'] ?? null;
            }
        }

        return $payload['id'] ?? null;
    }

    private function findExamination(string $externalId, string $partnerId): ?Examination
    {
        // Buscar pelo external_id direto
        $exam = Examination::where('external_id', $externalId)
            ->where('partner_integration_id', $partnerId)
            ->first();

        if ($exam) {
            return $exam;
        }

        // Buscar pelo mapeamento FHIR
        $mapping = FhirResourceMapping::where('fhir_resource_id', $externalId)
            ->where('partner_integration_id', $partnerId)
            ->where('internal_resource_type', FhirResourceMapping::INTERNAL_EXAMINATION)
            ->first();

        if ($mapping) {
            return Examination::find($mapping->internal_resource_id);
        }

        return null;
    }

    private function sanitizePayloadForAudit(array $payload): array
    {
        $entryCount = is_array($payload['entry'] ?? null) ? count($payload['entry']) : 0;

        return array_filter([
            'resource_type' => $payload['resourceType'] ?? null,
            'bundle_type' => $payload['type'] ?? null,
            'id' => $payload['id'] ?? null,
            'entry_count' => $entryCount,
        ], static fn ($value) => $value !== null);
    }

    private function isSafeAttachmentUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parsedScheme = parse_url($url, PHP_URL_SCHEME);
        if (! is_string($parsedScheme) || strtolower($parsedScheme) !== 'https') {
            return false;
        }

        return true;
    }
}
