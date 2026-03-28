<?php

namespace App\Integrations\Adapters\Lab;

use App\Integrations\Adapters\BaseAdapter;
use App\Integrations\Contracts\LabIntegrationInterface;
use App\Integrations\DTOs\ExamOrderDto;
use App\Integrations\DTOs\ExamResultDto;
use App\Integrations\Mappers\ExamOrderFhirMapper;
use App\Integrations\Mappers\ExamResultFhirMapper;
use App\Models\PartnerIntegration;

/**
 * Adapter genérico FHIR R4 para laboratórios.
 *
 * Laboratórios que usam FHIR R4 nativo usam este adapter diretamente.
 * Laboratórios com protocolo proprietário terão adapters específicos
 * que estendem BaseAdapter diretamente.
 */
class FhirLabAdapter extends BaseAdapter implements LabIntegrationInterface
{
    public function __construct(
        private readonly ExamOrderFhirMapper $orderMapper,
        private readonly ExamResultFhirMapper $resultMapper,
    ) {}

    public function healthCheck(PartnerIntegration $partner): array
    {
        try {
            $response = $this->httpClient($partner)->get('/metadata');

            return [
                'status' => $response->successful() ? 'ok' : 'error',
                'message' => $response->successful()
                    ? 'FHIR server acessível'
                    : 'FHIR server retornou status ' . $response->status(),
            ];
        } catch (\Throwable $e) {
            $this->log('error', 'Health check falhou', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getCapabilities(): array
    {
        return ['send_exam_order', 'receive_exam_result', 'webhook_result'];
    }

    public function getPartnerType(): string
    {
        return PartnerIntegration::TYPE_LABORATORY;
    }

    public function sendOrder(PartnerIntegration $partner, ExamOrderDto $order): array
    {
        $fhirPayload = $this->orderMapper->toFhir($order);

        $this->log('info', 'Enviando pedido de exame via FHIR', [
            'partner_id' => $partner->id,
            'examination_id' => $order->examinationId,
        ]);

        $response = $this->httpClient($partner)->post('/ServiceRequest', $fhirPayload);

        $response->throw();

        $body = $response->json();

        $externalId = $body['id'] ?? $body['identifier'][0]['value'] ?? '';

        if (empty($externalId)) {
            throw new \InvalidArgumentException(
                'Resposta do servidor FHIR não contém id nem identifier: não é possível rastrear o pedido'
            );
        }

        return [
            'external_id' => $externalId,
            'status' => 'sent',
        ];
    }

    public function fetchResult(PartnerIntegration $partner, string $externalId): ?ExamResultDto
    {
        $this->log('info', 'Buscando resultado de exame', [
            'partner_id' => $partner->id,
            'external_id' => $externalId,
        ]);

        $response = $this->httpClient($partner)
            ->get("/DiagnosticReport", ['based-on' => "ServiceRequest/{$externalId}"]);

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $body = $response->json();

        // Bundle com entries ou recurso direto
        if (isset($body['entry']) && is_array($body['entry']) && !empty($body['entry']) && isset($body['entry'][0]['resource'])) {
            $report = $body['entry'][0]['resource'];
        } elseif (isset($body['entry'])) {
            $this->log('warning', 'Resposta FHIR com entry vazio ou sem resource', [
                'partner_id' => $partner->id,
                'external_id' => $externalId,
            ]);
            return null;
        } else {
            $report = $body;
        }

        if (! $report) {
            $this->log('info', 'DiagnosticReport não encontrado na resposta', [
                'partner_id' => $partner->id,
                'external_id' => $externalId,
            ]);
            return null;
        }

        if (($report['status'] ?? '') !== 'final') {
            $this->log('info', 'DiagnosticReport ainda não finalizado', [
                'partner_id' => $partner->id,
                'external_id' => $externalId,
                'report_status' => $report['status'] ?? 'unknown',
            ]);
            return null;
        }

        return $this->resultMapper->fromFhir($report, $body['entry'] ?? []);
    }

    public function parseWebhookPayload(array $payload): ExamResultDto
    {
        $report = null;

        if (($payload['resourceType'] ?? '') === 'DiagnosticReport') {
            $report = $payload;
            $entries = $payload['entry'] ?? [$payload];
        } else {
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $resource = $entry['resource'] ?? $entry;
                if (($resource['resourceType'] ?? '') === 'DiagnosticReport') {
                    $report = $resource;
                    break;
                }
            }
        }

        if (! $report) {
            throw new \InvalidArgumentException('Webhook payload não contém DiagnosticReport');
        }

        return $this->resultMapper->fromFhir($report, $entries);
    }
}
