<?php

namespace Database\Seeders;

use App\Models\IntegrationQueueItem;
use App\Models\PartnerIntegration;
use Illuminate\Database\Seeder;

/**
 * Seeder da fila de integração (integration_queue).
 *
 * Popula itens em diferentes estados para facilitar desenvolvimento
 * e testes da UI de monitoramento.
 */
class IntegrationQueueSeeder extends Seeder
{
    public function run(): void
    {
        $hermes = PartnerIntegration::where('slug', 'hermes-pardini')->first();
        $fleury = PartnerIntegration::where('slug', 'fleury')->first();

        if (! $hermes || ! $fleury) {
            $this->command?->warn('IntegrationQueueSeeder requer PartnerIntegrationSeeder executado antes.');

            return;
        }

        // 2 itens pendentes (prontos para processamento)
        IntegrationQueueItem::factory()->count(2)->pending()->create([
            'partner_integration_id' => $hermes->id,
            'operation' => IntegrationQueueItem::OP_SEND_EXAM_ORDER,
        ]);

        // 1 item que falhou permanentemente (atingiu max_attempts)
        IntegrationQueueItem::factory()->failed()->create([
            'partner_integration_id' => $hermes->id,
            'operation' => IntegrationQueueItem::OP_SEND_EXAM_ORDER,
            'last_error' => 'HTTP 500: Internal Server Error after 5 retries',
        ]);

        // 1 item com retry agendado para o futuro (backoff)
        IntegrationQueueItem::factory()->futureRetry()->create([
            'partner_integration_id' => $fleury->id,
            'operation' => IntegrationQueueItem::OP_FETCH_EXAM_RESULT,
            'attempts' => 2,
            'last_error' => 'HTTP 503: Service Unavailable',
        ]);

        // 1 item completado (histórico)
        IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $fleury->id,
            'operation' => IntegrationQueueItem::OP_SEND_EXAM_ORDER,
            'status' => IntegrationQueueItem::STATUS_COMPLETED,
            'attempts' => 1,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(9),
        ]);

        // 1 item em processamento (cenário de worker ativo)
        IntegrationQueueItem::factory()->create([
            'partner_integration_id' => $hermes->id,
            'operation' => IntegrationQueueItem::OP_FETCH_EXAM_RESULT,
            'status' => IntegrationQueueItem::STATUS_PROCESSING,
            'attempts' => 1,
            'started_at' => now()->subSeconds(30),
        ]);
    }
}
