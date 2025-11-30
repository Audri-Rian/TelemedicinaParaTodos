<?php

namespace App\Observers;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentCreated;
use App\Events\AppointmentRescheduled;
use App\Events\AppointmentStatusChanged;
use App\Models\AppointmentLog;
use App\Models\Appointments;

class AppointmentsObserver
{
    /**
     * Handle the Appointments "creating" event.
     */
    public function creating(Appointments $appointment): void
    {
        if (!$appointment->access_code) {
            $appointment->access_code = self::generateUniqueAccessCode();
        }

        if (!$appointment->status) {
            $appointment->status = Appointments::STATUS_SCHEDULED;
        }
    }

    /**
     * Handle the Appointments "created" event.
     */
    public function created(Appointments $appointment): void
    {
        $appointment->logEvent(
            AppointmentLog::EVENT_CREATED,
            [
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
                'access_code' => $appointment->access_code,
            ],
            auth()->id()
        );

        // Disparar evento de notificação
        event(new AppointmentCreated($appointment->fresh()));
    }

    /**
     * Handle the Appointments "updated" event.
     */
    public function updated(Appointments $appointment): void
    {
        // Detectar mudanças de status
        if ($appointment->wasChanged('status')) {
            $oldStatus = $appointment->getOriginal('status');
            $newStatus = $appointment->status;

            // Disparar eventos de notificação baseado na mudança de status
            if ($newStatus === Appointments::STATUS_CANCELLED) {
                $reason = $appointment->notes ?: null;
                event(new AppointmentCancelled($appointment->fresh(), $reason));
            } elseif ($newStatus === Appointments::STATUS_RESCHEDULED) {
                $oldScheduledAt = $appointment->getOriginal('scheduled_at');
                if ($oldScheduledAt) {
                    $oldScheduledAt = is_string($oldScheduledAt) 
                        ? $oldScheduledAt 
                        : \Carbon\Carbon::parse($oldScheduledAt)->toIso8601String();
                    event(new AppointmentRescheduled($appointment->fresh(), $oldScheduledAt));
                }
            }

            // Logs específicos de mudança de status já são criados pelo Service
            // Aqui registramos apenas mudanças gerais
            if (!in_array($newStatus, [
                Appointments::STATUS_IN_PROGRESS,
                Appointments::STATUS_COMPLETED,
                Appointments::STATUS_CANCELLED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_NO_SHOW,
            ])) {
                $appointment->logEvent(
                    AppointmentLog::EVENT_UPDATED,
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'changed_fields' => array_keys($appointment->getChanges()),
                    ],
                    auth()->id()
                );
            }

            event(new AppointmentStatusChanged($appointment->fresh()));
        } elseif ($appointment->wasChanged('scheduled_at')) {
            // Reagendamento detectado pela mudança de scheduled_at
            $oldScheduledAt = $appointment->getOriginal('scheduled_at');
            if ($oldScheduledAt && $appointment->status === Appointments::STATUS_SCHEDULED) {
                $oldScheduledAt = is_string($oldScheduledAt) 
                    ? $oldScheduledAt 
                    : \Carbon\Carbon::parse($oldScheduledAt)->toIso8601String();
                event(new AppointmentRescheduled($appointment->fresh(), $oldScheduledAt));
            }
        } else {
            // Mudanças em outros campos (não status)
            $appointment->logEvent(
                AppointmentLog::EVENT_UPDATED,
                [
                    'changed_fields' => array_keys($appointment->getChanges()),
                ],
                auth()->id()
            );
        }
    }

    /**
     * Handle the Appointments "deleted" event.
     */
    public function deleted(Appointments $appointment): void
    {
        $appointment->logEvent(
            AppointmentLog::EVENT_DELETED,
            [
                'deleted_at' => now()->toIso8601String(),
            ],
            auth()->id()
        );
    }

    /**
     * Generate unique access code
     */
    private static function generateUniqueAccessCode(): string
    {
        $code = strtoupper(substr(md5(uniqid()), 0, 8));
        while (Appointments::where('access_code', $code)->exists()) {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        }
        return $code;
    }
}


