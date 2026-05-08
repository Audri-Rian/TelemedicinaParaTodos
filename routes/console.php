<?php

use App\Integrations\Jobs\ProcessIntegrationQueue;
use App\Integrations\Jobs\SyncExamResults;
use App\Jobs\CleanExpiredRedisLocks;
use App\Jobs\EndZombieVideoCalls;
use App\Jobs\MarkNoShowAppointments;
use App\Jobs\SendAppointmentReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendar envio de lembretes de consultas (frequência em config/telemedicine.php)
Schedule::job(new SendAppointmentReminders)
    ->cron(config('telemedicine.reminders.schedule_cron', '0 * * * *'))
    ->withoutOverlapping()
    ->onOneServer();

// Manutenção — marcar consultas vencidas como no_show
Schedule::job(new MarkNoShowAppointments)
    ->cron(config('telemedicine.maintenance.no_show_cron', '*/5 * * * *'))
    ->withoutOverlapping()
    ->onOneServer();

// Manutenção — finalizar chamadas de vídeo presas/zumbis
Schedule::job(new EndZombieVideoCalls)
    ->cron(config('telemedicine.maintenance.video_zombie_cleanup_cron', '*/5 * * * *'))
    ->withoutOverlapping()
    ->onOneServer();

// Manutenção — limpar locks Redis órfãos configurados
Schedule::job(new CleanExpiredRedisLocks)
    ->cron(config('telemedicine.maintenance.redis_lock_cleanup_cron', '*/15 * * * *'))
    ->withoutOverlapping()
    ->onOneServer();

// Interoperabilidade — sync de resultados de exames (pull de laboratórios)
Schedule::job(new SyncExamResults)
    ->cron(config('integrations.sync.exam_results_cron', '*/15 * * * *'))
    ->withoutOverlapping()
    ->onOneServer();

// Interoperabilidade — processar fila de retry
Schedule::job(new ProcessIntegrationQueue)
    ->cron(config('integrations.sync.retry_queue_cron', '*/5 * * * *'))
    ->withoutOverlapping()
    ->onOneServer();

// Storage — healthcheck por domínio para monitoramento operacional
Schedule::command('storage:health-check --fail-on-error')
    ->cron(config('telemedicine.storage.healthcheck_cron', '*/5 * * * *'))
    ->withoutOverlapping()
    ->onOneServer();

// Storage — limpeza automática por retenção (inicialmente lgpd_exports=7 dias)
Schedule::command('storage:cleanup-expired')
    ->cron(config('telemedicine.storage.retention_cleanup_cron', '0 2 * * *'))
    ->withoutOverlapping()
    ->onOneServer();
