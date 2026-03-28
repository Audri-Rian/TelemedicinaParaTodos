<?php

use App\Integrations\Jobs\ProcessIntegrationQueue;
use App\Integrations\Jobs\SyncExamResults;
use App\Jobs\SendAppointmentReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendar envio de lembretes de consultas (frequência em config/telemedicine.php)
Schedule::job(new SendAppointmentReminders())
    ->cron(config('telemedicine.reminders.schedule_cron', '0 * * * *'));

// Interoperabilidade — sync de resultados de exames (pull de laboratórios)
Schedule::job(new SyncExamResults())
    ->cron(config('integrations.sync.exam_results_cron', '*/15 * * * *'));

// Interoperabilidade — processar fila de retry
Schedule::job(new ProcessIntegrationQueue())
    ->cron(config('integrations.sync.retry_queue_cron', '*/5 * * * *'));
