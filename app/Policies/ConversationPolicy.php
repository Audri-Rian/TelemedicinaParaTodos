<?php

namespace App\Policies;

use App\Models\Appointments;
use App\Models\User;

/**
 * Policy for conversation/message access. There is no Conversation model;
 * authorization is based on the pair (current user, other user) and the rule
 * that only users with at least one appointment in common (doctor–patient) may
 * view the conversation or send messages.
 */
class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation with another user.
     */
    public function viewConversation(User $user, string $otherUserId): bool
    {
        if ($user->id === $otherUserId) {
            return true;
        }

        return $this->usersHaveAppointmentInCommon($user->id, $otherUserId);
    }

    /**
     * Determine whether the user can send a message to the given receiver.
     */
    public function sendMessage(User $user, string $receiverId): bool
    {
        if ($user->id === $receiverId) {
            return false;
        }

        return $this->usersHaveAppointmentInCommon($user->id, $receiverId);
    }

    /**
     * Check if two users have at least one appointment in common (as doctor and patient).
     */
    protected function usersHaveAppointmentInCommon(string $userId1, string $userId2): bool
    {
        $user1 = User::with(['doctor', 'patient'])->find($userId1);
        $user2 = User::with(['doctor', 'patient'])->find($userId2);

        if (!$user1 || !$user2) {
            return false;
        }

        $doctorId = $user1->doctor?->id ?? $user2->doctor?->id;
        $patientId = $user1->patient?->id ?? $user2->patient?->id;

        if (!$doctorId || !$patientId) {
            return false;
        }

        return Appointments::query()
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->exists();
    }

    /**
     * Check if the user can send a message in the context of this appointment (user and receiver are doctor/patient of the appointment).
     */
    public function sendMessageInAppointment(User $user, string $appointmentId, string $receiverId): bool
    {
        $appointment = Appointments::find($appointmentId);
        if (!$appointment) {
            return false;
        }

        $user->loadMissing(['doctor', 'patient']);
        $receiver = User::with(['doctor', 'patient'])->find($receiverId);
        if (!$receiver) {
            return false;
        }

        $userIsDoctor = $user->doctor && (string) $user->doctor->id === (string) $appointment->doctor_id;
        $userIsPatient = $user->patient && (string) $user->patient->id === (string) $appointment->patient_id;
        $receiverIsDoctor = $receiver->doctor && (string) $receiver->doctor->id === (string) $appointment->doctor_id;
        $receiverIsPatient = $receiver->patient && (string) $receiver->patient->id === (string) $appointment->patient_id;

        return ($userIsDoctor && $receiverIsPatient) || ($userIsPatient && $receiverIsDoctor);
    }
}
