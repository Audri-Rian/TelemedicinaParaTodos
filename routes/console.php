<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendar envio de lembretes de consultas (frequência em config/telemedicine.php)
Schedule::call(function () {
    \App\Jobs\SendAppointmentReminders::dispatch();
})->cron(config('telemedicine.reminders.schedule_cron', '0 * * * *'));
