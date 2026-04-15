<?php

namespace Tests\Unit\Integrations\Jobs;

use App\Integrations\Jobs\SendToRnds;
use App\Integrations\Mappers\PatientFhirMapper;
use App\Integrations\Services\CircuitBreaker;
use App\Models\Examination;
use App\Models\FhirResourceMapping;
use App\Models\IntegrationEvent;
use App\Models\IntegrationQueueItem;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class SendToRndsTest extends TestCase
{
    use RefreshDatabase;

    private PatientFhirMapper $patientMapper;
    private CircuitBreaker $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patientMapper = new PatientFhirMapper();
        $this->circuitBreaker = Mockery::mock(CircuitBreaker::class);

        config([
            'integrations.rnds.enabled' => true,
            'integrations.rnds.base_url' => 'https://ehr-services-hmg.saude.gov.br/api',
            'integrations.rnds.auth_url' => 'https://ehr-auth-hmg.saude.gov.br/api',
            'integrations.rnds.certificate_path' => '/tmp/fake-cert.pfx',
            'integrations.rnds.certificate_password' => 'fake-password',
            'integrations.rnds.cnes' => '1234567',
            'integrations.fhir.system_url' => 'https://telemedicina.example.com/fhir',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createCompletedExam(array $attrs = []): Examination
    {
        return Examination::withoutEvents(fn () => Examination::factory()->create(array_merge([
            'type' => Examination::TYPE_LAB,
            'name' => 'Hemograma completo',
            'status' => Examination::STATUS_COMPLETED,
            'completed_at' => now(),
            'results' => [
                ['name' => 'Hemoglobina', 'value' => 14.2, 'unit' => 'g/dL', 'loinc_code' => '718-7'],
            ],
        ], $attrs)));
    }

    public function test_skips_when_rnds_is_disabled(): void
    {
        config(['integrations.rnds.enabled' => false]);

        $exam = $this->createCompletedExam();
        $this->circuitBreaker->shouldNotReceive('isAvailable');

        Http::fake();

        $job = new SendToRnds($exam->id);
        $job->handle($this->patientMapper, $this->circuitBreaker);

        Http::assertNothingSent();
        $this->assertDatabaseMissing('integration_events', [
            'resource_id' => $exam->id,
            'event_type' => IntegrationEvent::EVENT_RNDS_SUBMITTED,
        ]);
    }

    public function test_skips_when_examination_is_not_completed(): void
    {
        $exam = $this->createCompletedExam(['status' => Examination::STATUS_IN_PROGRESS]);
        $this->circuitBreaker->shouldNotReceive('isAvailable');

        Http::fake();

        $job = new SendToRnds($exam->id);
        $job->handle($this->patientMapper, $this->circuitBreaker);

        Http::assertNothingSent();
        $this->assertDatabaseMissing('integration_events', [
            'resource_id' => $exam->id,
            'event_type' => IntegrationEvent::EVENT_RNDS_SUBMITTED,
        ]);
    }

    public function test_happy_path_posts_bundle_and_records_success(): void
    {
        $exam = $this->createCompletedExam();

        $this->circuitBreaker->shouldReceive('isAvailable')->andReturn(true);
        $this->circuitBreaker->shouldReceive('recordSuccess')->once();

        Http::fake([
            'ehr-auth-hmg.saude.gov.br/*' => Http::response([
                'access_token' => 'fake-rnds-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            'ehr-services-hmg.saude.gov.br/*' => Http::response([
                'resourceType' => 'Bundle',
                'id' => 'rnds-bundle-response-id',
            ], 200),
        ]);

        $job = new SendToRnds($exam->id);
        $job->handle($this->patientMapper, $this->circuitBreaker);

        // Deve ter criado parceiro virtual RNDS
        $rndsPartner = PartnerIntegration::where('slug', 'rnds-datasus')->first();
        $this->assertNotNull($rndsPartner);
        $this->assertEquals(PartnerIntegration::TYPE_RNDS, $rndsPartner->type);

        // Evento success registrado
        $this->assertDatabaseHas('integration_events', [
            'partner_integration_id' => $rndsPartner->id,
            'resource_id' => $exam->id,
            'event_type' => IntegrationEvent::EVENT_RNDS_SUBMITTED,
            'status' => IntegrationEvent::STATUS_SUCCESS,
        ]);

        // Mapping criado (idempotência)
        $this->assertTrue(
            FhirResourceMapping::alreadySynced('examination', $exam->id, $rndsPartner->id)
        );

        // POST ao endpoint /Bundle com Authorization Bearer
        Http::assertSent(function (Request $req) {
            return str_contains($req->url(), '/Bundle')
                && $req->method() === 'POST'
                && $req->hasHeader('Authorization', 'Bearer fake-rnds-token')
                && ($req->data()['resourceType'] ?? null) === 'Bundle';
        });
    }

    public function test_bundle_contains_expected_resources(): void
    {
        $exam = $this->createCompletedExam();

        $this->circuitBreaker->shouldReceive('isAvailable')->andReturn(true);
        $this->circuitBreaker->shouldReceive('recordSuccess')->once();

        Http::fake([
            'ehr-auth-hmg.saude.gov.br/*' => Http::response(['access_token' => 'tkn'], 200),
            'ehr-services-hmg.saude.gov.br/*' => Http::response(['id' => 'x'], 200),
        ]);

        $job = new SendToRnds($exam->id);
        $job->handle($this->patientMapper, $this->circuitBreaker);

        Http::assertSent(function (Request $req) {
            if (! str_contains($req->url(), '/Bundle')) {
                return false;
            }
            $types = array_map(
                fn ($entry) => $entry['resource']['resourceType'] ?? null,
                $req->data()['entry'] ?? []
            );

            return in_array('Patient', $types, true)
                && in_array('Practitioner', $types, true)
                && in_array('DiagnosticReport', $types, true)
                && in_array('Observation', $types, true);
        });
    }

    public function test_already_synced_exam_is_skipped(): void
    {
        $exam = $this->createCompletedExam();

        // Pré-criar o parceiro RNDS e mapping (simular envio anterior)
        $rndsPartner = PartnerIntegration::firstOrCreate(
            ['slug' => 'rnds-datasus'],
            [
                'name' => 'RNDS (DATASUS)',
                'type' => PartnerIntegration::TYPE_RNDS,
                'status' => PartnerIntegration::STATUS_ACTIVE,
                'capabilities' => ['submit_bundle'],
            ],
        );

        FhirResourceMapping::create([
            'internal_resource_type' => 'examination',
            'internal_resource_id' => $exam->id,
            'fhir_resource_type' => FhirResourceMapping::FHIR_COMPOSITION,
            'fhir_resource_id' => 'previous-submission',
            'partner_integration_id' => $rndsPartner->id,
            'synced_at' => now()->subHour(),
        ]);

        // shouldNotReceive é o idioma correto — o mix ->shouldReceive->andReturn->never
        // é contraditório e confunde leitura do teste.
        $this->circuitBreaker->shouldNotReceive('isAvailable');
        Http::fake();

        $job = new SendToRnds($exam->id);
        $job->handle($this->patientMapper, $this->circuitBreaker);

        Http::assertNothingSent();
    }

    public function test_enqueues_retry_when_circuit_is_open(): void
    {
        $exam = $this->createCompletedExam();

        $this->circuitBreaker->shouldReceive('isAvailable')->andReturn(false);
        $this->circuitBreaker->shouldReceive('getCoolingTimeout')->andReturn(120);

        Http::fake();

        $job = new SendToRnds($exam->id);
        $job->handle($this->patientMapper, $this->circuitBreaker);

        $this->assertDatabaseHas('integration_queue', [
            'operation' => IntegrationQueueItem::OP_SUBMIT_RNDS,
            'status' => IntegrationQueueItem::STATUS_QUEUED,
        ]);

        Http::assertNothingSent();
    }

    public function test_records_failure_and_rethrows_when_rnds_returns_5xx(): void
    {
        $exam = $this->createCompletedExam();

        $this->circuitBreaker->shouldReceive('isAvailable')->andReturn(true);
        $this->circuitBreaker->shouldReceive('recordFailure')->once();

        Http::fake([
            'ehr-auth-hmg.saude.gov.br/*' => Http::response(['access_token' => 'tkn'], 200),
            'ehr-services-hmg.saude.gov.br/*' => Http::response(['error' => 'internal'], 500),
        ]);

        $job = new SendToRnds($exam->id);

        // Esperamos uma RequestException (Http::throw() → 5xx). Validar tipo e
        // re-throw garante que qualquer outra exceção acusada pelo código
        // apareceria no teste em vez de ser silenciosamente engolida.
        try {
            $job->handle($this->patientMapper, $this->circuitBreaker);
            $this->fail('Esperava RequestException pois RNDS retornou 500');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->assertEquals(500, $e->response->status());
        }

        $rndsPartner = PartnerIntegration::where('slug', 'rnds-datasus')->first();
        $this->assertDatabaseHas('integration_events', [
            'partner_integration_id' => $rndsPartner->id,
            'resource_id' => $exam->id,
            'status' => IntegrationEvent::STATUS_FAILED,
            'http_status' => 500, // prova que extraímos status do RequestException
        ]);
    }

    public function test_throws_when_certificate_is_not_configured(): void
    {
        config([
            'integrations.rnds.certificate_path' => null,
        ]);

        $exam = $this->createCompletedExam();

        $this->circuitBreaker->shouldReceive('isAvailable')->andReturn(true);
        $this->circuitBreaker->shouldReceive('recordFailure')->once();

        Http::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('certificado');

        $job = new SendToRnds($exam->id);

        try {
            $job->handle($this->patientMapper, $this->circuitBreaker);
        } finally {
            // Mesmo com a exceção esperada, validamos que nenhum request HTTP saiu.
            Http::assertNothingSent();
        }
    }

    public function test_uses_rnds_queue_from_config(): void
    {
        $job = new SendToRnds('fake-id');
        $this->assertEquals(
            config('integrations.queue.name', 'integrations'),
            $job->queue
        );
    }
}
