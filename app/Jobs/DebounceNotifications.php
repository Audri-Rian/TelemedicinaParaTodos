<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;

class DebounceNotifications implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $userId,
        public NotificationType $type,
        public array $metadata,
        public array $channels = ['in_app']
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $key = $this->getDebounceKey();

        // Verificar se ainda existe a chave (não foi processada por outro job)
        if (!Redis::exists($key)) {
            return;
        }

        // Obter dados consolidados
        $dataJson = Redis::get($key);
        if (!$dataJson) {
            return;
        }

        $data = json_decode($dataJson, true);
        
        if (!$data) {
            return;
        }

        // Consolidar metadata (usar a mais recente)
        $consolidatedMetadata = $this->consolidateMetadata($data['metadata'] ?? $this->metadata);

        // Criar notificação consolidada (sem debounce para evitar loop)
        $notificationService->create(
            $this->type,
            $consolidatedMetadata,
            $this->userId,
            $this->channels,
            true // skipDebounce = true
        );

        // Remover chave de debounce
        Redis::del($key);
    }

    /**
     * Obter chave de debounce
     */
    private function getDebounceKey(): string
    {
        $context = $this->metadata['appointment_id'] ?? 'general';
        return "notification_debounce:{$this->userId}:{$this->type->value}:{$context}";
    }

    /**
     * Consolidar metadata de múltiplas notificações
     */
    private function consolidateMetadata(array $metadata): array
    {
        // Se houver múltiplas alterações, consolidar informações
        // Por exemplo, se houve múltiplos reagendamentos, usar o mais recente
        if (isset($metadata['old_scheduled_at']) && isset($metadata['new_scheduled_at'])) {
            // Manter apenas a última alteração
            return $metadata;
        }

        return $metadata;
    }
}

