<?php

namespace App\Policies;

use App\Models\Appointments;
use App\Models\Call;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class VideoCallPolicy
{
    /**
     * Gate: iniciar chamada ad-hoc. Paciente precisa ter relacionamento recente (D10).
     */
    public function requestAdhoc(User $user, Doctor $doctor): bool
    {
        if (! $user->patient) {
            return false;
        }

        $relationshipDays = (int) config('telemedicine.video_call.ad_hoc_relationship_days', 7);

        return Appointments::where('doctor_id', $doctor->id)
            ->where('patient_id', $user->patient->id)
            ->whereNotIn('status', [Appointments::STATUS_CANCELLED])
            ->where('ended_at', '>=', now()->subDays($relationshipDays))
            ->exists();
    }

    /**
     * Gate: aceitar chamada ad-hoc. Apenas o médico da call pode aceitar.
     */
    public function accept(User $user, Call $call): bool
    {
        if ($call->call_type !== Call::TYPE_AD_HOC) {
            return false;
        }

        return $user->doctor !== null && (string) $user->doctor->id === (string) $call->doctor_id;
    }

    /**
     * Gate: recusar chamada ad-hoc. Apenas o médico da call pode recusar.
     */
    public function reject(User $user, Call $call): bool
    {
        return $this->accept($user, $call);
    }

    /**
     * Gate: encerrar chamada (ambos os tipos). Qualquer participante pode encerrar.
     */
    public function end(User $user, Call $call): bool
    {
        return $this->view($user, $call);
    }

    /**
     * Gate: ver detalhes de uma call específica.
     */
    public function view(User $user, Call $call): bool
    {
        return ($user->doctor && (string) $user->doctor->id === (string) $call->doctor_id)
            || ($user->patient && (string) $user->patient->id === (string) $call->patient_id);
    }

    /**
     * Gate: verificar se existe call ativa para o usuário.
     */
    public function viewActive(User $user): bool
    {
        return $user->doctor !== null || $user->patient !== null;
    }

    /**
     * Gate: entrar na sala de vídeo de um appointment.
     * Apenas o médico ou o paciente do appointment, dentro da janela de tempo ou em andamento.
     */
    public function joinSession(User $user, Appointments $appointment): bool
    {
        $isParticipant = ($user->doctor && (string) $user->doctor->id === (string) $appointment->doctor_id)
            || ($user->patient && (string) $user->patient->id === (string) $appointment->patient_id);

        if (! $isParticipant) {
            return false;
        }

        if ($appointment->status === Appointments::STATUS_IN_PROGRESS) {
            if ($appointment->isWithinInProgressWindow()) {
                return true;
            }

            Log::info('VIDEO_CALL_JOIN_BLOCKED_WINDOW_EXPIRED', [
                'appointment_id' => $appointment->id,
                'status' => $appointment->status,
                'minutes_past_window' => (int) $appointment->inProgressWindowEndsAt()->diffInMinutes(now()),
            ]);

            return false;
        }

        if (! in_array($appointment->status, [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED], true)) {
            return false;
        }

        $leadMinutes = (int) config('telemedicine.video_call.window_lead_minutes', 10);
        $trailingMinutes = (int) config('telemedicine.video_call.window_trailing_minutes', 10);
        $now = \Carbon\Carbon::now();
        $diff = (int) round(($appointment->scheduled_at->timestamp - $now->timestamp) / 60);

        return $diff >= -$trailingMinutes && $diff <= $leadMinutes;
    }

    // Mantido por compatibilidade com qualquer referência legada
    public function request(User $user, Appointments $appointment): bool
    {
        return false;
    }
}
