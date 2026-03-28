<?php

namespace App\Integrations\Adapters\Lab;

use App\Integrations\Adapters\BaseAdapter;
use App\Integrations\Contracts\LabIntegrationInterface;
use App\Integrations\DTOs\ExamOrderDto;
use App\Integrations\DTOs\ExamResultDto;
use App\Models\PartnerIntegration;
use Illuminate\Support\Str;

/**
 * Stub de laboratório para testes e desenvolvimento.
 *
 * Simula um laboratório parceiro sem chamar API real.
 * Segue o padrão existente em MediaGatewayStub.
 */
class LabAdapterStub extends BaseAdapter implements LabIntegrationInterface
{
    public function healthCheck(PartnerIntegration $partner): array
    {
        return ['status' => 'ok', 'message' => 'Stub — sempre disponível'];
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
        $this->log('info', '[STUB] Pedido de exame simulado', [
            'examination_id' => $order->examinationId,
        ]);

        return [
            'external_id' => 'stub-' . Str::uuid()->toString(),
            'status' => 'sent',
        ];
    }

    public function fetchResult(PartnerIntegration $partner, string $externalId): ?ExamResultDto
    {
        $this->log('info', '[STUB] Fetch de resultado simulado', [
            'external_id' => $externalId,
        ]);

        // Simula resultado pronto 50% das vezes
        if (rand(0, 1) === 0) {
            return null;
        }

        return new ExamResultDto(
            externalId: $externalId,
            examinationId: null,
            status: 'final',
            results: [
                [
                    'name' => 'Hemoglobina',
                    'value' => round(rand(110, 170) / 10, 1),
                    'unit' => 'g/dL',
                    'reference_range' => '12.0-17.5',
                    'status' => 'normal',
                    'loinc_code' => '718-7',
                ],
                [
                    'name' => 'Glicemia em jejum',
                    'value' => $glicemia = rand(70, 130),
                    'unit' => 'mg/dL',
                    'reference_range' => '70-99',
                    'status' => $glicemia <= 99 ? 'normal' : 'high',
                    'loinc_code' => '1558-6',
                ],
            ],
            completedAt: now()->toIso8601String(),
        );
    }

    public function parseWebhookPayload(array $payload): ExamResultDto
    {
        if (isset($payload['results']) && !is_array($payload['results'])) {
            throw new \InvalidArgumentException('O campo "results" do payload deve ser um array');
        }

        return new ExamResultDto(
            externalId: $payload['id'] ?? 'stub-webhook-' . Str::uuid()->toString(),
            examinationId: null,
            status: 'final',
            results: $payload['results'] ?? [],
            completedAt: now()->toIso8601String(),
        );
    }
}
