<?php

namespace App\Policies;

use App\Models\Appointments;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Médicos e pacientes podem ver seus próprios appointments
        return $user->isDoctor() || $user->isPatient();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Appointments $appointment): bool
    {
        // Apenas médico ou paciente do appointment podem ver
        if ($user->isDoctor()) {
            return $appointment->doctor_id === $user->doctor->id;
        }
        
        if ($user->isPatient()) {
            return $appointment->patient_id === $user->patient->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Apenas pacientes podem criar appointments
        return $user->isPatient();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Appointments $appointment): bool
    {
        // Apenas médico ou paciente do appointment podem atualizar
        // E apenas se não estiver em progresso (campos críticos imutáveis)
        if ($appointment->status === Appointments::STATUS_IN_PROGRESS) {
            return false;
        }
        
        if ($user->isDoctor()) {
            return $appointment->doctor_id === $user->doctor->id;
        }
        
        if ($user->isPatient()) {
            return $appointment->patient_id === $user->patient->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appointments $appointment): bool
    {
        // Apenas administradores ou se não estiver em progresso/completed
        if (in_array($appointment->status, [
            Appointments::STATUS_IN_PROGRESS,
            Appointments::STATUS_COMPLETED
        ])) {
            return false;
        }
        
        if ($user->isDoctor()) {
            return $appointment->doctor_id === $user->doctor->id;
        }
        
        if ($user->isPatient()) {
            return $appointment->patient_id === $user->patient->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Appointments $appointment): bool
    {
        // Apenas administradores
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Appointments $appointment): bool
    {
        // Apenas administradores
        return false;
    }

    /**
     * Determine whether the user can start the appointment.
     */
    public function start(User $user, Appointments $appointment): bool
    {
        // Apenas médico ou paciente do appointment podem iniciar
        if (!$this->view($user, $appointment)) {
            return false;
        }
        
        // Deve estar scheduled ou rescheduled
        if (!in_array($appointment->status, [
            Appointments::STATUS_SCHEDULED,
            Appointments::STATUS_RESCHEDULED
        ])) {
            return false;
        }
        
        // Validar janela de tempo (lead_minutes antes do horário)
        $leadMinutes = config('telemedicine.appointment.lead_minutes', 10);
        $canStartAt = $appointment->scheduled_at->copy()->subMinutes($leadMinutes);
        
        return Carbon::now() >= $canStartAt;
    }

    /**
     * Determine whether the user can end the appointment.
     */
    public function end(User $user, Appointments $appointment): bool
    {
        // Apenas médico ou paciente do appointment podem finalizar
        if (!$this->view($user, $appointment)) {
            return false;
        }
        
        // Deve estar em progresso
        return $appointment->status === Appointments::STATUS_IN_PROGRESS;
    }

    /**
     * Determine whether the user can cancel the appointment.
     */
    public function cancel(User $user, Appointments $appointment): bool
    {
        // Apenas médico ou paciente do appointment podem cancelar
        if (!$this->view($user, $appointment)) {
            return false;
        }
        
        // Deve estar scheduled ou rescheduled
        if (!in_array($appointment->status, [
            Appointments::STATUS_SCHEDULED,
            Appointments::STATUS_RESCHEDULED
        ])) {
            return false;
        }
        
        // Validar janela de cancelamento (cancel_before_hours antes do horário)
        $cancelBeforeHours = config('telemedicine.appointment.cancel_before_hours', 2);
        $canCancelUntil = $appointment->scheduled_at->copy()->subHours($cancelBeforeHours);
        
        return Carbon::now() <= $canCancelUntil;
    }

    /**
     * Determine whether the user can reschedule the appointment.
     */
    public function reschedule(User $user, Appointments $appointment): bool
    {
        // Apenas médico ou paciente do appointment podem reagendar
        if (!$this->view($user, $appointment)) {
            return false;
        }
        
        // Deve estar scheduled ou rescheduled
        if (!in_array($appointment->status, [
            Appointments::STATUS_SCHEDULED,
            Appointments::STATUS_RESCHEDULED
        ])) {
            return false;
        }
        
        // Validar janela de cancelamento (mesma regra do cancel)
        $cancelBeforeHours = config('telemedicine.appointment.cancel_before_hours', 2);
        $canRescheduleUntil = $appointment->scheduled_at->copy()->subHours($cancelBeforeHours);
        
        return Carbon::now() <= $canRescheduleUntil;
    }

    /**
     * Determine whether the user (doctor) can add clinical data to this appointment.
     * Only the doctor of the appointment can do so, and typically only when in_progress or completed.
     */
    protected function doctorCanActOnAppointment(User $user, Appointments $appointment, array $allowedStatuses): bool
    {
        if (!$user->isDoctor() || !$user->doctor) {
            return false;
        }

        if ($appointment->doctor_id !== $user->doctor->id) {
            return false;
        }

        return in_array($appointment->status, $allowedStatuses, true);
    }

    /**
     * Statuses in which the doctor can add prescriptions, diagnoses, notes, etc.
     */
    protected function clinicalActionStatuses(): array
    {
        return [
            Appointments::STATUS_IN_PROGRESS,
            Appointments::STATUS_COMPLETED,
        ];
    }

    public function createPrescription(User $user, Appointments $appointment): bool
    {
        return $this->doctorCanActOnAppointment($user, $appointment, $this->clinicalActionStatuses());
    }

    public function requestExamination(User $user, Appointments $appointment): bool
    {
        return $this->doctorCanActOnAppointment($user, $appointment, $this->clinicalActionStatuses());
    }

    public function registerDiagnosis(User $user, Appointments $appointment): bool
    {
        return $this->doctorCanActOnAppointment($user, $appointment, $this->clinicalActionStatuses());
    }

    public function createNote(User $user, Appointments $appointment): bool
    {
        return $this->doctorCanActOnAppointment($user, $appointment, $this->clinicalActionStatuses());
    }

    public function issueCertificate(User $user, Appointments $appointment): bool
    {
        return $this->doctorCanActOnAppointment($user, $appointment, $this->clinicalActionStatuses());
    }

    public function registerVitalSigns(User $user, Appointments $appointment): bool
    {
        return $this->doctorCanActOnAppointment($user, $appointment, $this->clinicalActionStatuses());
    }

    public function generateConsultationPdf(User $user, Appointments $appointment): bool
    {
        return $this->doctorCanActOnAppointment($user, $appointment, $this->clinicalActionStatuses());
    }

    /**
     * Doctor can save draft (metadata/notes) when appointment is in progress or completed.
     */
    public function saveDraft(User $user, Appointments $appointment): bool
    {
        return $this->doctorCanActOnAppointment($user, $appointment, $this->clinicalActionStatuses());
    }

    /**
     * Doctor can complement (notes) only when appointment is completed.
     */
    public function complement(User $user, Appointments $appointment): bool
    {
        if (!$user->isDoctor() || !$user->doctor) {
            return false;
        }
        if ($appointment->doctor_id !== $user->doctor->id) {
            return false;
        }

        return $appointment->status === Appointments::STATUS_COMPLETED;
    }
}
