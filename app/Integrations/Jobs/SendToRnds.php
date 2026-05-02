<?php

namespace App\Integrations\Jobs;

use App\Integrations\DTOs\FhirBundleDto;
use App\Integrations\Mappers\PatientFhirMapper;
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
use Illuminate\Support\Facades\Http;
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

    public int $backoff = 60;

    public function __construct(public readonly string $examinationId)
    {
        $this->onQueue(config('integrations.queue.name', 'integrations'));
    }

    public function handle(
        PatientFhirMapper $patientMapper,
        CircuitBreaker $circuitBreaker,
    ): void {
        if (! config('integrations.rnds.enabled')) {
            Log::channel('integration')->debug('RNDS desabilitada — pulando envio', [
                'examination_id' => $this->examinationId,
            ]);

            return;
        }

        $examination = Examination::with(['patient.user', 'doctor.user', 'appointment'])
            ->findOrFail($this->examinationId);

        if ($examination->status !== Examination::STATUS_COMPLETED) {
            Log::channel('integration')->warning('RNDS: exame não está completo — pulando', [
                'examination_id' => $examination->id,
                'status' => $examination->status,
            ]);

            return;
        }

        // FHIR exige subject.reference — exame sem patient_id não pode ser enviado.
        if (! $examination->patient_id) {
            Log::channel('integration')->error('RNDS: exame sem patient_id — impossível montar Bundle', [
                'examination_id' => $examination->id,
            ]);

            return;
        }

        // Pegar ou criar registro de PartnerIntegration do tipo RNDS (representa "parceiro" virtual)
        $rndsPartner = $this->ensureRndsPartner();

        // Idempotência: já foi enviado?
        if (FhirResourceMapping::alreadySynced('examination', $examination->id, $rndsPartner->id)) {
            Log::channel('integration')->info('RNDS: exame já submetido anteriormente', [
                'examination_id' => $examination->id,
            ]);

            return;
        }

        // Circuit breaker (tipo 'rnds' conforme config)
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
            $bundle = $this->buildBundle($examination, $patientMapper);
            $accessToken = $this->obtainAccessToken();
            $response = $this->postBundle($bundle, $accessToken);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $event->update([
                'status' => IntegrationEvent::STATUS_SUCCESS,
                'http_status' => $response->status(),
                'external_id' => $response->json('id') ?? $bundle->bundleId,
                'response_payload' => $response->json(),
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

            // Extrair HTTP status real quando for RequestException (Laravel Http client).
            // $e->getCode() frequentemente retorna 0 ou código PHP sem relação ao status HTTP.
            $httpStatus = $e instanceof RequestException && $e->response
                ? $e->response->status()
                : ($e->getCode() > 0 ? $e->getCode() : null);

            $event->update([
                'status' => IntegrationEvent::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'http_status' => $httpStatus,
            ]);

            $circuitBreaker->recordFailure($rndsPartner->id);

            Log::channel('integration')->error('RNDS: falha no envio', [
                'examination_id' => $examination->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // deixa o worker reprocessar via $tries/$backoff
        }
    }

    /**
     * Constrói o Bundle FHIR para submissão à RNDS.
     *
     * Estrutura mínima: Bundle (document) com Composition + Patient + DiagnosticReport + Observation[].
     * NOTA: Para produção real, verificar perfis BR Core específicos que RNDS exige em cada recurso.
     */
    private function buildBundle(
        Examination $examination,
        PatientFhirMapper $patientMapper,
    ): FhirBundleDto {
        $bundleId = (string) Str::uuid();

        $entries = [];

        // Patient
        if ($examination->patient) {
            $entries[] = ['resource' => $patientMapper->toFhir($examination->patient)];
        }

        // Practitioner (médico solicitante) — só adicionamos se houver doctor real.
        // Bundle sem Practitioner é aceito pela RNDS; Practitioner sem id/nome não é.
        $practitioner = $this->buildPractitioner($examination);
        if ($practitioner !== null) {
            $entries[] = ['resource' => $practitioner];
        }

        // Encounter (consulta associada)
        if ($examination->appointment_id) {
            $entries[] = ['resource' => $this->buildEncounter($examination)];
        }

        // DiagnosticReport + Observations (resultados do exame)
        [$report, $observations] = $this->buildDiagnosticReportAndObservations($examination);
        $entries[] = ['resource' => $report];
        foreach ($observations as $observation) {
            $entries[] = ['resource' => $observation];
        }

        return new FhirBundleDto(
            type: 'document',
            entries: $entries,
            bundleId: $bundleId,
        );
    }

    /**
     * Monta Practitioner FHIR a partir do médico associado.
     *
     * Retorna null quando não há doctor associado — o caller deve pular o
     * recurso do Bundle em vez de emitir um Practitioner com id=null.
     */
    private function buildPractitioner(Examination $examination): ?array
    {
        $doctor = $examination->doctor;

        if (! $doctor) {
            return null;
        }

        $resource = [
            'resourceType' => 'Practitioner',
            'id' => $doctor->id,
            'identifier' => [],
            'name' => [['text' => $doctor->user?->name ?? '']],
        ];

        if ($doctor->cns) {
            $resource['identifier'][] = [
                'system' => 'http://rnds.saude.gov.br/fhir/r4/NamingSystem/cns',
                'value' => $doctor->cns,
            ];
        }

        // TODO: adicionar CRM quando o campo estiver disponível no model Doctor
        return $resource;
    }

    /**
     * Monta Encounter FHIR da consulta associada ao exame.
     */
    private function buildEncounter(Examination $examination): array
    {
        return [
            'resourceType' => 'Encounter',
            'id' => $examination->appointment_id,
            'status' => 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject' => [
                'reference' => "Patient/{$examination->patient_id}",
            ],
        ];
    }

    /**
     * Converte resultados do exame em DiagnosticReport + Observation[] FHIR.
     */
    private function buildDiagnosticReportAndObservations(Examination $examination): array
    {
        $reportId = 'report-'.$examination->id;
        $observations = [];
        $observationRefs = [];

        foreach (($examination->results ?? []) as $index => $result) {
            $obsId = "obs-{$examination->id}-{$index}";
            $observationRefs[] = ['reference' => "Observation/{$obsId}"];

            $observations[] = [
                'resourceType' => 'Observation',
                'id' => $obsId,
                'status' => 'final',
                'code' => [
                    'coding' => isset($result['loinc_code']) ? [[
                        'system' => 'http://loinc.org',
                        'code' => $result['loinc_code'],
                        'display' => $result['name'] ?? 'Resultado',
                    ]] : [],
                    'text' => $result['name'] ?? 'Resultado',
                ],
                'subject' => ['reference' => "Patient/{$examination->patient_id}"],
                'valueQuantity' => is_numeric($result['value'] ?? null) ? [
                    'value' => $result['value'],
                    'unit' => $result['unit'] ?? '',
                ] : null,
                'valueString' => ! is_numeric($result['value'] ?? null) ? ($result['value'] ?? null) : null,
            ];
        }

        $report = [
            'resourceType' => 'DiagnosticReport',
            'id' => $reportId,
            'status' => 'final',
            'code' => ['text' => $examination->name],
            'subject' => ['reference' => "Patient/{$examination->patient_id}"],
            'effectiveDateTime' => $examination->completed_at?->toIso8601String(),
            'issued' => now()->toIso8601String(),
            'result' => $observationRefs,
        ];

        if ($examination->attachment_url) {
            $report['presentedForm'] = [[
                'url' => $examination->attachment_url,
                'contentType' => 'application/pdf',
            ]];
        }

        // Limpar nulls dentro das Observations
        $observations = array_map(
            fn ($obs) => array_filter($obs, fn ($v) => $v !== null),
            $observations
        );

        return [$report, $observations];
    }

    /**
     * Obtém token OAuth2 do provedor RNDS via mTLS com certificado e-CNPJ.
     *
     * NOTA DE ESCOPO: este método requer certificado e-CNPJ configurado.
     * Sem ele, o job emite warning e lança exceção — o que leva o Laravel
     * a reenfileirar conforme $tries/$backoff. Para o MVP atual (sem
     * infraestrutura RNDS), basta manter RNDS_ENABLED=false no .env.
     */
    private function obtainAccessToken(): string
    {
        $cacheKey = 'integrations:rnds:oauth_access_token';
        $cachedToken = Cache::get($cacheKey);
        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $certPath = config('integrations.rnds.certificate_path');
        $certPassword = config('integrations.rnds.certificate_password');
        $authUrl = config('integrations.rnds.auth_url');
        $timeouts = config('integrations.timeouts.rnds', ['connect' => 10, 'response' => 60]);

        if (! $certPath || ! $authUrl) {
            throw new \RuntimeException(
                'RNDS: certificado e-CNPJ ou URL de autenticação não configurados.'
            );
        }

        $options = ['cert' => $certPassword ? [$certPath, $certPassword] : $certPath];

        $response = Http::withOptions($options)
            ->timeout($timeouts['response'])
            ->connectTimeout($timeouts['connect'])
            ->asForm()
            ->post(rtrim($authUrl, '/').'/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            Log::channel('integration')->warning('RNDS auth falhou', [
                'http_status' => $response->status(),
            ]);

            throw new \RuntimeException(
                "RNDS auth falhou: HTTP {$response->status()}",
                $response->status(),
            );
        }

        $token = $response->json('access_token');

        if (! $token) {
            throw new \RuntimeException('RNDS: resposta de auth sem access_token.');
        }

        $expiresIn = max(60, ((int) $response->json('expires_in', 300)) - 30);
        Cache::put($cacheKey, $token, now()->addSeconds($expiresIn));

        return $token;
    }

    /**
     * Envia o Bundle FHIR para o endpoint EHR da RNDS.
     */
    private function postBundle(FhirBundleDto $bundle, string $accessToken)
    {
        $baseUrl = config('integrations.rnds.base_url');
        $cnes = config('integrations.rnds.cnes');
        $timeouts = config('integrations.timeouts.rnds', ['connect' => 10, 'response' => 60]);

        if (! $baseUrl) {
            throw new \RuntimeException('RNDS: base_url não configurada.');
        }

        // Headers condicionais: X-Authorization-Server só é enviado quando há CNES.
        // Enviar o header vazio pode causar rejeição por alguns WAFs.
        $headers = [
            'Accept' => 'application/fhir+json',
            'Content-Type' => 'application/fhir+json',
        ];
        if ($cnes) {
            $headers['X-Authorization-Server'] = "CNES/{$cnes}";
        }

        return Http::withToken($accessToken)
            ->withHeaders($headers)
            ->timeout($timeouts['response'])
            ->connectTimeout($timeouts['connect'])
            ->post(rtrim($baseUrl, '/').'/Bundle', $bundle->toFhirJson())
            ->throw();
    }

    /**
     * Garante que existe um PartnerIntegration representando a RNDS.
     *
     * RNDS é tratada como um "parceiro virtual" para aproveitar toda a
     * infraestrutura de eventos, circuit breaker e queue já existente.
     */
    private function ensureRndsPartner(): PartnerIntegration
    {
        return PartnerIntegration::firstOrCreate(
            ['slug' => 'rnds-datasus'],
            [
                'name' => 'RNDS (DATASUS)',
                'type' => PartnerIntegration::TYPE_RNDS,
                'status' => PartnerIntegration::STATUS_ACTIVE,
                'base_url' => config('integrations.rnds.base_url'),
                'capabilities' => ['submit_bundle'],
                'fhir_version' => 'R4',
            ],
        );
    }

    /**
     * Enfileira nova tentativa quando circuit breaker está aberto.
     */
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
}
