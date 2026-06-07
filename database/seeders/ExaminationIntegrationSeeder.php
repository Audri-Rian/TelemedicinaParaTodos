<?php

namespace Database\Seeders;

use App\Models\Examination;
use App\Models\FhirResourceMapping;
use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder de exames em diferentes estágios do ciclo de integração.
 *
 * Depende de PartnerIntegrationSeeder: associa exames a parceiros existentes
 * (Lab Hermes Pardini e Fleury) para refletir cenários realistas.
 */
class ExaminationIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $hermes = PartnerIntegration::where('slug', 'hermes-pardini')->first();
        $fleury = PartnerIntegration::where('slug', 'fleury')->first();

        // Early return fora da transação — não queremos começar uma trx só para abortar.
        if (! $hermes || ! $fleury) {
            $this->command?->warn('ExaminationIntegrationSeeder requer PartnerIntegrationSeeder executado antes.');

            return;
        }

        // Atomicidade: ou cria tudo ou nada. Evita estado parcial se algo falhar
        // no meio (ex.: FK violation em FhirResourceMapping após criar Examination).
        DB::transaction(function () use ($hermes, $fleury): void {
            // 1. Exames recém solicitados (ainda não enviados ao lab)
            Examination::factory()->count(2)->create([
                'type' => Examination::TYPE_LAB,
                'name' => $this->randomExamName(),
                'status' => Examination::STATUS_REQUESTED,
                'source' => Examination::SOURCE_INTERNAL,
                'results' => null,
                'completed_at' => null,
            ]);

            // 2. Exames enviados ao lab, aguardando resultado
            for ($i = 0; $i < 3; $i++) {
                $partner = $i % 2 === 0 ? $hermes : $fleury;
                $externalId = 'EXT-'.strtoupper(Str::random(10));
                $doctorId = $this->resolveDoctorIdForPartner($partner);

                $examination = Examination::factory()->create([
                    'type' => Examination::TYPE_LAB,
                    'name' => $this->randomExamName(),
                    'status' => Examination::STATUS_IN_PROGRESS,
                    'source' => Examination::SOURCE_INTERNAL,
                    'doctor_id' => $doctorId,
                    'partner_integration_id' => $partner->id,
                    'external_id' => $externalId,
                    'results' => null,
                    'completed_at' => null,
                ]);

                FhirResourceMapping::create([
                    'partner_integration_id' => $partner->id,
                    'internal_resource_type' => FhirResourceMapping::INTERNAL_EXAMINATION,
                    'internal_resource_id' => $examination->id,
                    'fhir_resource_type' => FhirResourceMapping::FHIR_SERVICE_REQUEST,
                    'fhir_resource_id' => $externalId,
                    'synced_at' => now()->subHours(2),
                ]);

                IntegrationEvent::factory()->outbound()->successful()->create([
                    'partner_integration_id' => $partner->id,
                    'doctor_id' => $doctorId,
                    'event_type' => IntegrationEvent::EVENT_EXAM_ORDER_SENT,
                    'resource_type' => 'examination',
                    'resource_id' => $examination->id,
                    'external_id' => $externalId,
                    'fhir_resource_type' => FhirResourceMapping::FHIR_SERVICE_REQUEST,
                ]);
            }

            // 3. Exames com resultados recebidos via integração
            for ($i = 0; $i < 2; $i++) {
                $partner = $i === 0 ? $hermes : $fleury;
                $externalId = 'EXT-'.strtoupper(Str::random(10));
                $doctorId = $this->resolveDoctorIdForPartner($partner);

                $examination = Examination::factory()->create([
                    'type' => Examination::TYPE_LAB,
                    'name' => $this->randomExamName(),
                    'status' => Examination::STATUS_COMPLETED,
                    'source' => Examination::SOURCE_INTEGRATION,
                    'doctor_id' => $doctorId,
                    'partner_integration_id' => $partner->id,
                    'external_id' => $externalId,
                    'external_accession' => 'ACC-'.strtoupper(Str::random(8)),
                    'received_from_partner_at' => now()->subMinutes(30),
                    'completed_at' => now()->subMinutes(30),
                    'results' => [
                        ['name' => 'Hemoglobina', 'value' => 14.2, 'unit' => 'g/dL', 'reference_range' => '13.0-17.0', 'status' => 'normal'],
                        ['name' => 'Glicemia', 'value' => 92, 'unit' => 'mg/dL', 'reference_range' => '70-99', 'status' => 'normal'],
                    ],
                ]);

                FhirResourceMapping::create([
                    'partner_integration_id' => $partner->id,
                    'internal_resource_type' => FhirResourceMapping::INTERNAL_EXAMINATION,
                    'internal_resource_id' => $examination->id,
                    'fhir_resource_type' => FhirResourceMapping::FHIR_DIAGNOSTIC_REPORT,
                    'fhir_resource_id' => $externalId,
                    'synced_at' => now()->subMinutes(30),
                ]);

                IntegrationEvent::factory()->inbound()->successful()->create([
                    'partner_integration_id' => $partner->id,
                    'doctor_id' => $doctorId,
                    'event_type' => IntegrationEvent::EVENT_EXAM_RESULT_RECEIVED,
                    'resource_type' => 'examination',
                    'resource_id' => $examination->id,
                    'external_id' => $externalId,
                    'fhir_resource_type' => FhirResourceMapping::FHIR_DIAGNOSTIC_REPORT,
                ]);
            }
        });
    }

    private function randomExamName(): string
    {
        return fake()->randomElement([
            'Hemograma completo',
            'Glicemia em jejum',
            'TSH',
            'Colesterol total e frações',
            'Urina tipo I',
            'Creatinina',
        ]);
    }

    private function resolveDoctorIdForPartner(PartnerIntegration $partner): string
    {
        return $partner
            ->doctors()
            ->orderByPivot('created_at')
            ->value('doctors.id') ?? throw new \RuntimeException(
                "PartnerIntegration '{$partner->slug}' sem vínculo em doctor_partner_integrations."
            );
    }
}
