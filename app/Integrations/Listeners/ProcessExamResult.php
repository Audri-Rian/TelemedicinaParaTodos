<?php

namespace App\Integrations\Listeners;

use App\Enums\NotificationType;
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
                type: NotificationType::EXAM_RESULT_RECEIVED,
                metadata: [
                    'examination_id' => $examination->id,
                    'examination_name' => $examination->name,
                    'patient_id' => $examination->patient_id,
                    'patient_name' => $examination->patient?->user?->name ?? 'Paciente',
                    'partner_id' => $event->partner->id,
                    'partner_name' => $event->partner->name,
                ],
                user: $examination->doctor->user,
            );
        }

        // Notificar paciente
        if ($examination->patient?->user) {
            $this->notificationService->create(
                type: NotificationType::EXAM_RESULT_RECEIVED,
                metadata: [
                    'examination_id' => $examination->id,
                    'examination_name' => $examination->name,
                ],
                user: $examination->patient->user,
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
