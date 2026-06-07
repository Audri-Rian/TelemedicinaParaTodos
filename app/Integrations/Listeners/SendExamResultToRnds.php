<?php

namespace App\Integrations\Listeners;

use App\Integrations\Events\ExamResultReceived;
use App\Integrations\Jobs\SendToRnds;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Dispara submissão automática à RNDS quando um resultado de exame é recebido.
 *
 * Só executa se RNDS_ENABLED=true. Caso contrário, é no-op — permite que
 * o código fique pronto mesmo antes do registro no DATASUS ser concluído.
 */
class SendExamResultToRnds implements ShouldQueue
{
    /**
     * Número de tentativas antes do listener ir para failed_jobs.
     * O job SendToRnds disparado também tem retry próprio — aqui estamos
     * protegendo apenas a etapa de enfileiramento.
     */
    public int $tries = 3;

    /**
     * Segundos entre tentativas em caso de falha.
     */
    public int $backoff = 60;

    /**
     * Nome da fila. Usamos viaQueue() em vez de setar no construtor porque
     * o listener é serializado/deserializado pelo worker e chamar config()
     * no __construct não sobrevive à serialização.
     */
    public function viaQueue(): string
    {
        return config('integrations.queue.name', 'integrations');
    }

    public function handle(ExamResultReceived $event): void
    {
        if (! config('integrations.rnds.enabled')) {
            return;
        }

        SendToRnds::dispatch($event->examination->id);
    }

    public function failed(ExamResultReceived $event, \Throwable $exception): void
    {
        // Passamos a exceção completa no contexto: o Monolog preserva
        // stack trace automaticamente quando recebe Throwable via key 'exception'.
        Log::channel('integration')->error('Falha ao disparar SendToRnds', [
            'examination_id' => $event->examination->id ?? null,
            'exception' => $exception,
        ]);
    }
}
