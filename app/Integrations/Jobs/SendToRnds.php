<?php

namespace App\Integrations\Jobs;

use App\Integrations\Mappers\PatientFhirMapper;
use App\Integrations\Rnds\Clients\RndsBundleClient;
use App\Integrations\Rnds\Fhir\RndsExaminationBundleBuilder;
use App\Integrations\Rnds\Services\RndsPartnerRegistrar;
use App\Integrations\Rnds\Token\RndsTokenManager;
use App\Integrations\Services\CircuitBreaker;
use App\Models\Examination;
use App\Models\FhirResourceMapping;
use App\Models\IntegrationEvent;
use App\Models\IntegrationQueueItem;
use App\Models\PartnerIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Job de submissão de Bundle FHIR para a RNDS (Rede Nacional de Dados em Saúde).
 *
 * Fluxo:
 *  1. Obtém token OAuth2 do provedor RNDS usando certificado e-CNPJ (mTLS)
 *  2. Monta Bundle FHIR (Patient + DiagnosticReport + Observation[])
 *  3. POST ao endpoint EHR da RNDS
 *  4. Registra IntegrationEvent + FhirResourceMapping para rastreabilidade
 *
 * Pré-requisitos (FORA DO ESCOPO DESTE MVP — processo burocrático externo):
 *  - Registro da aplicação no Portal de Serviços DATASUS
 *  - CNES ativo do estabelecimento (RNDS_CNES)
 *  - Certificado e-CNPJ válido (A1 .pfx; A3 requer hardware dedicado)
 *  - Aprovação de acesso ao ambiente de homologação RNDS
 *
 * Referência:
 *  - execute/PadroesRegulatorios.md
 *  - https://servicos-datasus.saude.gov.br/
 *  - docs/interoperabilidade/Arquitetura.md (seção RNDS)
 *
 * Implementação modular: app/Integrations/Rnds/{Certificate,Token,Clients,Fhir,Services,DTOs}
 *
 * TODO (fase pós-MVP):
 *  - Aplicar perfis BR Core (meta.profile) nos recursos quando RNDS exigir
 *  - Implementar verificação de conformidade com validador RNDS antes do envio
 *  - Extensões brasileiras (raça/cor, município IBGE) no Patient
 */
class SendToRnds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(public readonly string $examinationId)
    {
        $this->onQueue(config('integrations.queue.name', 'integrations'));
    }

    public function handle(
        PatientFhirMapper $patientMapper,
        CircuitBreaker $circuitBreaker,
        RndsExaminationBundleBuilder $bundleBuilder,
        RndsTokenManager $tokenManager,
        RndsBundleClient $bundleClient,
        RndsPartnerRegistrar $partnerRegistrar,
    ): void {
        if (! config('integrations.rnds.enabled')) {
            Log::channel('integration')->debug('RNDS desabilitada — pulando envio', [
                'examination_id' => $this->examinationId,
            ]);

            return;
        }

        $lock = Cache::lock($this->lockKey(), $this->timeout + 30);

        if (! $lock->get()) {
            Log::channel('integration')->info('RNDS: envio já em processamento para o exame', [
                'examination_id' => $this->examinationId,
            ]);

            return;
        }

        try {
            $this->send(
                $patientMapper,
                $circuitBreaker,
                $bundleBuilder,
                $tokenManager,
                $bundleClient,
                $partnerRegistrar,
            );
        } finally {
            $lock->release();
        }
    }

    private function send(
        PatientFhirMapper $patientMapper,
        CircuitBreaker $circuitBreaker,
        RndsExaminationBundleBuilder $bundleBuilder,
        RndsTokenManager $tokenManager,
        RndsBundleClient $bundleClient,
        RndsPartnerRegistrar $partnerRegistrar,
    ): void {
        $examination = Examination::with(['patient.user', 'doctor.user', 'appointment'])
            ->findOrFail($this->examinationId);

        if ($examination->status !== Examination::STATUS_COMPLETED) {
            Log::channel('integration')->warning('RNDS: exame não está completo — pulando', [
                'examination_id' => $examination->id,
                'status' => $examination->status,
            ]);

            return;
        }

        if (! $examination->patient_id) {
            Log::channel('integration')->error('RNDS: exame sem patient_id — impossível montar Bundle', [
                'examination_id' => $examination->id,
            ]);

            return;
        }

        $rndsPartner = $partnerRegistrar->ensurePartner();

        if (FhirResourceMapping::alreadySynced('examination', $examination->id, $rndsPartner->id)) {
            Log::channel('integration')->info('RNDS: exame já submetido anteriormente', [
                'examination_id' => $examination->id,
            ]);

            return;
        }

        if (! $circuitBreaker->isAvailable($rndsPartner->id)) {
            $this->requeue(
                $rndsPartner,
                'Circuito aberto — aguardando cooling',
                $circuitBreaker->getCoolingTimeout($rndsPartner->id),
            );

            return;
        }

        $event = IntegrationEvent::create([
            'partner_integration_id' => $rndsPartner->id,
            'doctor_id' => $examination->doctor_id,
            'direction' => IntegrationEvent::DIRECTION_OUTBOUND,
            'event_type' => IntegrationEvent::EVENT_RNDS_SUBMITTED,
            'status' => IntegrationEvent::STATUS_PROCESSING,
            'resource_type' => 'examination',
            'resource_id' => $examination->id,
            'fhir_resource_type' => 'Bundle',
        ]);

        $startTime = microtime(true);

        try {
            $bundle = $bundleBuilder->buildForExamination($examination, $patientMapper);
            $accessToken = $tokenManager->getAccessToken();
            $response = $bundleClient->submitBundle($bundle, $accessToken);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $event->update([
                'status' => IntegrationEvent::STATUS_SUCCESS,
                'http_status' => $response->status(),
                'external_id' => $response->json('id') ?? $bundle->bundleId,
                'response_payload' => $this->safeResponsePayload($response->json()),
                'duration_ms' => $durationMs,
            ]);

            FhirResourceMapping::create([
                'internal_resource_type' => FhirResourceMapping::INTERNAL_EXAMINATION,
                'internal_resource_id' => $examination->id,
                'fhir_resource_type' => FhirResourceMapping::FHIR_COMPOSITION,
                'fhir_resource_id' => $response->json('id') ?? $bundle->bundleId,
                'fhir_bundle_id' => $bundle->bundleId,
                'partner_integration_id' => $rndsPartner->id,
                'synced_at' => now(),
            ]);

            $circuitBreaker->recordSuccess($rndsPartner->id);
            $rndsPartner->update(['last_sync_at' => now()]);

            Log::channel('integration')->info('RNDS: Bundle submetido com sucesso', [
                'examination_id' => $examination->id,
                'bundle_id' => $bundle->bundleId,
            ]);

        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $httpStatus = $e instanceof RequestException && $e->response
                ? $e->response->status()
                : ($e->getCode() > 0 ? $e->getCode() : null);

            $event->update([
                'status' => IntegrationEvent::STATUS_FAILED,
                'error_message' => $this->safeErrorMessage($e, $httpStatus),
                'duration_ms' => $durationMs,
                'http_status' => $httpStatus,
            ]);

            $circuitBreaker->recordFailure($rndsPartner->id);

            Log::channel('integration')->error('RNDS: falha no envio', [
                'examination_id' => $examination->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function requeue(PartnerIntegration $partner, string $reason, int $coolingSeconds): void
    {
        IntegrationQueueItem::create([
            'partner_integration_id' => $partner->id,
            'operation' => IntegrationQueueItem::OP_SUBMIT_RNDS,
            'payload' => ['examination_id' => $this->examinationId],
            'status' => IntegrationQueueItem::STATUS_QUEUED,
            'max_attempts' => (int) config('integrations.retry.submit_rnds.max_attempts', 10),
            'last_error' => $reason,
            'scheduled_at' => now()->addSeconds($coolingSeconds),
        ]);
    }

    private function lockKey(): string
    {
        return "integrations:rnds:send:{$this->examinationId}";
    }

    private function safeResponsePayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $issues = collect($payload['issue'] ?? [])
            ->take(5)
            ->map(fn ($issue) => [
                'severity' => data_get($issue, 'severity'),
                'code' => data_get($issue, 'code'),
            ])
            ->filter(fn ($issue) => array_filter($issue) !== [])
            ->values()
            ->all();

        return array_filter([
            'resourceType' => $payload['resourceType'] ?? null,
            'id' => $payload['id'] ?? null,
            'issue_count' => is_countable($payload['issue'] ?? null) ? count($payload['issue']) : null,
            'issues' => $issues ?: null,
        ], fn ($value) => $value !== null);
    }

    private function safeErrorMessage(\Throwable $e, ?int $httpStatus): string
    {
        if ($e instanceof RequestException && $httpStatus !== null) {
            return "RNDS HTTP {$httpStatus}";
        }

        return Str::limit(class_basename($e).': '.$e->getMessage(), 500);
    }
}
