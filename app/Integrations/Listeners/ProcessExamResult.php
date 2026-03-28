<?php

namespace App\Integrations\Listeners;

use App\Integrations\Events\ExamResultReceived;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Processa resultado de exame recebido via integração.
 *
 * Envia notificações para médico e paciente quando um resultado
 * chega automaticamente do laboratório parceiro.
 */
class ProcessExamResult implements ShouldQueue
{
    public string $queue;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
        $this->queue = config('integrations.queue.name', 'integrations');
    }

    public function handle(ExamResultReceived $event): void
    {
        $examination = $event->examination;
        $examination->loadMissing(['patient.user', 'doctor.user']);

        // Notificar médico
        if ($examination->doctor?->user) {
            $this->notificationService->create(
                userId: $examination->doctor->user->id,
                type: 'exam_result_received',
                title: 'Resultado de exame recebido',
                message: "O resultado do exame \"{$examination->name}\" do paciente {$examination->patient?->user?->name ?? 'Paciente'} foi recebido automaticamente do laboratório.",
                data: [
                    'examination_id' => $examination->id,
                    'patient_id' => $examination->patient_id,
                    'partner_id' => $event->partner->id,
                ],
            );
        }

        // Notificar paciente
        if ($examination->patient?->user) {
            $this->notificationService->create(
                userId: $examination->patient->user->id,
                type: 'exam_result_received',
                title: 'Resultado de exame disponível',
                message: "O resultado do seu exame \"{$examination->name}\" já está disponível no prontuário.",
                data: [
                    'examination_id' => $examination->id,
                ],
            );
        }
    }

    public function failed(ExamResultReceived $event, \Throwable $exception): void
    {
        Log::channel('integration')->error('Falha ao processar resultado de exame', [
            'examination_id' => $event->examination->id ?? null,
            'exception' => $exception->getMessage(),
        ]);
    }
}
