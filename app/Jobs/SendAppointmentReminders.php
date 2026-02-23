<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Models\Appointments;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAppointmentReminders implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $now = Carbon::now();
        $sendBeforeHours = config('telemedicine.reminders.send_before_hours', [24, 1]);

        foreach ($sendBeforeHours as $hours) {
            $reminderTime = $now->copy()->addHours($hours);
            $appointments = Appointments::where('status', Appointments::STATUS_SCHEDULED)
                ->whereBetween('scheduled_at', [
                    $reminderTime->copy()->startOfHour(),
                    $reminderTime->copy()->endOfHour(),
                ])
                ->with(['doctor.user', 'patient.user'])
                ->get();

            $timeUntilLabel = $hours === 1 ? '1 hora' : $hours . ' horas';
            foreach ($appointments as $appointment) {
                $this->sendReminder($appointment, $notificationService, $timeUntilLabel);
            }
        }
    }

    /**
     * Enviar lembrete para uma consulta
     */
    private function sendReminder(
        Appointments $appointment,
        NotificationService $notificationService,
        string $timeUntil
    ): void {
        $doctorName = $appointment->doctor->user->name ?? 'Médico';
        $scheduledAt = $appointment->scheduled_at->format('d/m/Y H:i');

        // Notificar paciente
        $notificationService->create(
            NotificationType::APPOINTMENT_REMINDER,
            [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'doctor_name' => $doctorName,
                'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
                'time_until' => $timeUntil,
            ],
            $appointment->patient->user,
            ['email', 'in_app']
        );

        // Notificar médico
        $notificationService->create(
            NotificationType::APPOINTMENT_REMINDER,
            [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'patient_name' => $appointment->patient->user->name ?? 'Paciente',
                'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
                'time_until' => $timeUntil,
            ],
            $appointment->doctor->user,
            ['email', 'in_app']
        );
    }
}
