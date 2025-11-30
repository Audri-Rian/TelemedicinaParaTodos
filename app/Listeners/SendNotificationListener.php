<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentCreated;
use App\Events\AppointmentRescheduled;
use App\Events\ExaminationRequested;
use App\Events\MedicalCertificateIssued;
use App\Events\PrescriptionIssued;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * Handle AppointmentCreated event
     */
    public function handleAppointmentCreated(AppointmentCreated $event): void
    {
        $appointment = $event->appointment;
        $doctorName = $appointment->doctor->user->name ?? 'Médico';
        $patientName = $appointment->patient->user->name ?? 'Paciente';

        // Notificar paciente
        $this->notificationService->create(
            NotificationType::APPOINTMENT_CREATED,
            [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'doctor_name' => $doctorName,
                'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
            ],
            $appointment->patient->user,
            ['email', 'in_app']
        );

        // Notificar médico
        $this->notificationService->create(
            NotificationType::APPOINTMENT_CREATED,
            [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'patient_name' => $patientName,
                'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
            ],
            $appointment->doctor->user,
            ['email', 'in_app']
        );
    }

    /**
     * Handle AppointmentCancelled event
     */
    public function handleAppointmentCancelled(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;
        $doctorName = $appointment->doctor->user->name ?? 'Médico';
        $patientName = $appointment->patient->user->name ?? 'Paciente';

        $metadata = [
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => $appointment->patient_id,
            'doctor_name' => $doctorName,
            'patient_name' => $patientName,
            'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
        ];

        if ($event->reason) {
            $metadata['reason'] = $event->reason;
        }

        // Notificar paciente
        $this->notificationService->create(
            NotificationType::APPOINTMENT_CANCELLED,
            $metadata,
            $appointment->patient->user,
            ['email', 'in_app']
        );

        // Notificar médico
        $this->notificationService->create(
            NotificationType::APPOINTMENT_CANCELLED,
            $metadata,
            $appointment->doctor->user,
            ['email', 'in_app']
        );
    }

    /**
     * Handle AppointmentRescheduled event
     */
    public function handleAppointmentRescheduled(AppointmentRescheduled $event): void
    {
        $appointment = $event->appointment;
        $doctorName = $appointment->doctor->user->name ?? 'Médico';
        $patientName = $appointment->patient->user->name ?? 'Paciente';

        $metadata = [
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => $appointment->patient_id,
            'doctor_name' => $doctorName,
            'patient_name' => $patientName,
            'old_scheduled_at' => $event->oldScheduledAt,
            'new_scheduled_at' => $appointment->scheduled_at->toIso8601String(),
        ];

        // Notificar paciente
        $this->notificationService->create(
            NotificationType::APPOINTMENT_RESCHEDULED,
            $metadata,
            $appointment->patient->user,
            ['email', 'in_app']
        );

        // Notificar médico
        $this->notificationService->create(
            NotificationType::APPOINTMENT_RESCHEDULED,
            $metadata,
            $appointment->doctor->user,
            ['email', 'in_app']
        );
    }

    /**
     * Handle PrescriptionIssued event
     */
    public function handlePrescriptionIssued(PrescriptionIssued $event): void
    {
        $prescription = $event->prescription;
        $doctorName = $prescription->doctor->user->name ?? 'Médico';

        $this->notificationService->create(
            NotificationType::PRESCRIPTION_ISSUED,
            [
                'prescription_id' => $prescription->id,
                'appointment_id' => $prescription->appointment_id,
                'doctor_id' => $prescription->doctor_id,
                'doctor_name' => $doctorName,
            ],
            $prescription->patient->user,
            ['email', 'in_app']
        );
    }

    /**
     * Handle ExaminationRequested event
     */
    public function handleExaminationRequested(ExaminationRequested $event): void
    {
        $examination = $event->examination;
        $doctorName = $examination->doctor->user->name ?? 'Médico';

        $this->notificationService->create(
            NotificationType::EXAMINATION_REQUESTED,
            [
                'examination_id' => $examination->id,
                'appointment_id' => $examination->appointment_id,
                'doctor_id' => $examination->doctor_id,
                'doctor_name' => $doctorName,
                'examination_name' => $examination->name,
                'examination_type' => $examination->type,
            ],
            $examination->patient->user,
            ['email', 'in_app']
        );
    }

    /**
     * Handle MedicalCertificateIssued event
     */
    public function handleMedicalCertificateIssued(MedicalCertificateIssued $event): void
    {
        $certificate = $event->medicalCertificate;
        $doctorName = $certificate->doctor->user->name ?? 'Médico';

        $this->notificationService->create(
            NotificationType::MEDICAL_CERTIFICATE_ISSUED,
            [
                'certificate_id' => $certificate->id,
                'appointment_id' => $certificate->appointment_id,
                'doctor_id' => $certificate->doctor_id,
                'doctor_name' => $doctorName,
                'certificate_type' => $certificate->type,
                'verification_code' => $certificate->verification_code,
            ],
            $certificate->patient->user,
            ['email', 'in_app']
        );
    }
}


