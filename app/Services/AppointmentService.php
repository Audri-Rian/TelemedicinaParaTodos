<?php

namespace App\Services;

use App\Models\Appointments;
use App\Models\AppointmentLog;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AppointmentService
{
    public function isUpcoming(Appointments $appointment): bool
    {
        return $appointment->scheduled_at > Carbon::now() &&
            in_array($appointment->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED]);
    }

    public function isPast(Appointments $appointment): bool
    {
        return $appointment->scheduled_at < Carbon::now();
    }

    public function isActive(Appointments $appointment): bool
    {
        return $appointment->status === Appointments::STATUS_IN_PROGRESS;
    }

    public function canBeStarted(Appointments $appointment): bool
    {
        if (!in_array($appointment->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])) {
            return false;
        }
        
        $leadMinutes = config('telemedicine.appointment.lead_minutes', 10);
        $canStartAt = $appointment->scheduled_at->copy()->subMinutes($leadMinutes);
        
        return Carbon::now() >= $canStartAt;
    }

    public function canBeCancelled(Appointments $appointment): bool
    {
        if (!in_array($appointment->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])) {
            return false;
        }
        
        $cancelBeforeHours = config('telemedicine.appointment.cancel_before_hours', 2);
        $canCancelUntil = $appointment->scheduled_at->copy()->subHours($cancelBeforeHours);
        
        return Carbon::now() <= $canCancelUntil;
    }

    public function start(Appointments $appointment, ?string $userId = null): bool
    {
        if (!$this->canBeStarted($appointment)) {
            return false;
        }

        $appointment->update([
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now(),
        ]);

        // Criar log de início
        $appointment->logEvent(
            AppointmentLog::EVENT_STARTED,
            ['started_at' => Carbon::now()->toIso8601String()],
            $userId
        );

        return true;
    }

    public function end(Appointments $appointment, ?string $userId = null): bool
    {
        if ($appointment->status !== Appointments::STATUS_IN_PROGRESS) {
            return false;
        }

        $appointment->update([
            'status' => Appointments::STATUS_COMPLETED,
            'ended_at' => Carbon::now(),
        ]);

        // Criar log de finalização
        $appointment->logEvent(
            AppointmentLog::EVENT_ENDED,
            [
                'ended_at' => Carbon::now()->toIso8601String(),
                'duration_minutes' => $appointment->duration,
            ],
            $userId
        );

        return true;
    }

    public function cancel(Appointments $appointment, ?string $reason = null, ?string $userId = null): bool
    {
        if (!$this->canBeCancelled($appointment)) {
            return false;
        }

        $appointment->update([
            'status' => Appointments::STATUS_CANCELLED,
            'notes' => $reason ? ($appointment->notes . "\nCancelado: " . $reason) : $appointment->notes,
        ]);

        // Criar log de cancelamento
        $appointment->logEvent(
            AppointmentLog::EVENT_CANCELLED,
            ['reason' => $reason],
            $userId
        );

        return true;
    }

    public function markAsNoShow(Appointments $appointment, ?string $userId = null): bool
    {
        if ($appointment->status !== Appointments::STATUS_SCHEDULED) {
            return false;
        }

        $appointment->update([
            'status' => Appointments::STATUS_NO_SHOW,
        ]);

        // Criar log de no-show
        $appointment->logEvent(
            AppointmentLog::EVENT_NO_SHOW,
            ['marked_at' => Carbon::now()->toIso8601String()],
            $userId
        );

        return true;
    }

    public function reschedule(Appointments $appointment, Carbon $newDateTime, ?string $userId = null): bool
    {
        if (!in_array($appointment->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])) {
            return false;
        }

        // Validar conflito com novo horário
        if (!$this->validateNoConflict(
            $appointment->doctor_id,
            $newDateTime,
            config('telemedicine.appointment.duration_minutes', 30),
            $appointment->id
        )) {
            return false;
        }

        $oldScheduledAt = $appointment->scheduled_at;

        $appointment->update([
            'scheduled_at' => $newDateTime,
            'status' => Appointments::STATUS_RESCHEDULED,
        ]);

        // Criar log de reagendamento
        $appointment->logEvent(
            AppointmentLog::EVENT_RESCHEDULED,
            [
                'old_scheduled_at' => $oldScheduledAt->toIso8601String(),
                'new_scheduled_at' => $newDateTime->toIso8601String(),
            ],
            $userId
        );

        return true;
    }

    /**
     * Criar novo appointment
     */
    public function create(array $data, User $user): Appointments
    {
        // Validar doctor ativo
        if (!$this->validateDoctorActive($data['doctor_id'])) {
            throw new \Exception('Médico não está ativo.');
        }

        // Validar patient completo (deve ter completado segunda etapa de autenticação)
        if (!$this->validatePatientComplete($data['patient_id'])) {
            throw new \Exception('Paciente não possui cadastro completo. É necessário completar a segunda etapa de autenticação (contato de emergência) para agendar consultas.');
        }

        // Validar conflito de horário
        $duration = config('telemedicine.appointment.duration_minutes', 30);
        if (!$this->validateNoConflict($data['doctor_id'], Carbon::parse($data['scheduled_at']), $duration)) {
            throw new \Exception('Conflito de horário: médico já possui consulta neste período.');
        }

        // Criar appointment
        $appointment = Appointments::create([
            'doctor_id' => $data['doctor_id'],
            'patient_id' => $data['patient_id'],
            'scheduled_at' => $data['scheduled_at'],
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'status' => Appointments::STATUS_SCHEDULED,
        ]);

        // Log será criado automaticamente pelo Observer

        return $appointment->fresh();
    }

    /**
     * Atualizar appointment
     */
    public function update(Appointments $appointment, array $data, User $user): bool
    {
        // Campos críticos não podem ser alterados após in_progress
        if ($appointment->status === Appointments::STATUS_IN_PROGRESS) {
            unset($data['doctor_id'], $data['patient_id'], $data['scheduled_at']);
        }

        // Se tentar alterar scheduled_at, validar conflito
        if (isset($data['scheduled_at']) && $data['scheduled_at'] !== $appointment->scheduled_at->toIso8601String()) {
            $duration = config('telemedicine.appointment.duration_minutes', 30);
            if (!$this->validateNoConflict(
                $appointment->doctor_id,
                Carbon::parse($data['scheduled_at']),
                $duration,
                $appointment->id
            )) {
                throw new \Exception('Conflito de horário: médico já possui consulta neste período.');
            }
        }

        $appointment->update($data);

        // Log será criado automaticamente pelo Observer

        return true;
    }

    /**
     * Listar appointments com filtros
     */
    public function list(array $filters = []): Collection
    {
        $query = Appointments::query();

        // Filtro por doctor
        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        // Filtro por patient
        if (isset($filters['patient_id'])) {
            $query->byPatient($filters['patient_id']);
        }

        // Filtro por status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por data
        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $from = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : Carbon::now()->startOfDay();
            $to = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : Carbon::now()->endOfDay();
            $query->byDateRange($from, $to);
        }

        // Filtro upcoming
        if (isset($filters['upcoming']) && $filters['upcoming']) {
            $query->upcoming();
        }

        // Filtro past
        if (isset($filters['past']) && $filters['past']) {
            $query->past();
        }

        // Ordenar por scheduled_at
        $query->orderBy('scheduled_at', $filters['order'] ?? 'asc');

        return $query->with(['doctor.user', 'patient.user'])->get();
    }

    /**
     * Buscar appointment para usuário específico
     */
    public function findForUser(string $appointmentId, User $user): ?Appointments
    {
        $appointment = Appointments::find($appointmentId);

        if (!$appointment) {
            return null;
        }

        // Verificar acesso
        if ($user->isDoctor() && $appointment->doctor_id !== $user->doctor->id) {
            return null;
        }

        if ($user->isPatient() && $appointment->patient_id !== $user->patient->id) {
            return null;
        }

        return $appointment->load(['doctor.user', 'patient.user', 'logs']);
    }

    /**
     * Validar se não há conflito de horário
     */
    public function validateNoConflict(
        string $doctorId,
        Carbon $scheduledAt,
        ?int $duration = null,
        ?string $excludeAppointmentId = null
    ): bool {
        $duration = $duration ?? config('telemedicine.appointment.duration_minutes', 30);
        
        $startTime = $scheduledAt->copy();
        $endTime = $scheduledAt->copy()->addMinutes($duration);

        // Verificar conflitos: appointments que se sobrepõem
        $query = Appointments::where('doctor_id', $doctorId)
            ->whereIn('status', [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_IN_PROGRESS
            ])
            ->where(function ($q) use ($startTime, $endTime, $duration) {
                // Conflito: appointment existente começa durante o novo período
                $q->whereBetween('scheduled_at', [$startTime, $endTime])
                  // Conflito: appointment existente termina durante o novo período
                  ->orWhere(function ($q2) use ($startTime, $duration) {
                      $q2->where('scheduled_at', '<=', $startTime)
                         ->whereRaw('DATE_ADD(scheduled_at, INTERVAL ? MINUTE) > ?', [
                             $duration,
                             $startTime->toDateTimeString()
                         ]);
                  });
            });

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        return $query->count() === 0;
    }

    /**
     * Validar se doctor está ativo
     */
    public function validateDoctorActive(string $doctorId): bool
    {
        $doctor = Doctor::find($doctorId);
        
        return $doctor && $doctor->status === Doctor::STATUS_ACTIVE;
    }

    /**
     * Validar se patient tem cadastro completo
     * 
     * Valida se o paciente completou a segunda etapa de autenticação:
     * - Primeira etapa: email, senha, gênero, data de nascimento, telefone
     * - Segunda etapa: contato de emergência (obrigatório após primeira etapa)
     * 
     * @param string $patientId
     * @return bool
     */
    public function validatePatientComplete(string $patientId): bool
    {
        $patient = Patient::find($patientId);
        
        if (!$patient) {
            return false;
        }

        // Verificar campos da primeira etapa (obrigatórios no registro inicial)
        $firstStageComplete = !empty($patient->date_of_birth) && 
                             !empty($patient->phone_number) &&
                             !empty($patient->gender);

        // Verificar campos da segunda etapa (obrigatórios para agendar consultas)
        // Segundo SystemRules.md: "Contato de emergência obrigatório após a primeira etapa de autenticação"
        $secondStageComplete = !empty($patient->emergency_contact) && 
                              !empty($patient->emergency_phone);

        // Verificar status ativo
        $isActive = $patient->status === Patient::STATUS_ACTIVE;

        return $firstStageComplete && $secondStageComplete && $isActive;
    }

    /**
     * Validar transição de status
     */
    public function validateStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $allowedTransitions = [
            Appointments::STATUS_SCHEDULED => [
                Appointments::STATUS_IN_PROGRESS,
                Appointments::STATUS_CANCELLED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_NO_SHOW,
            ],
            Appointments::STATUS_RESCHEDULED => [
                Appointments::STATUS_IN_PROGRESS,
                Appointments::STATUS_CANCELLED,
                Appointments::STATUS_SCHEDULED,
            ],
            Appointments::STATUS_IN_PROGRESS => [
                Appointments::STATUS_COMPLETED,
            ],
            Appointments::STATUS_COMPLETED => [],
            Appointments::STATUS_CANCELLED => [],
            Appointments::STATUS_NO_SHOW => [],
        ];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }
}

