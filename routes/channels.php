<?php

use App\Models\Appointments;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels (anti-IDOR: user must be authenticated and linked to resource)
|--------------------------------------------------------------------------
| appointments.{uuid} — only the doctor or patient of that appointment may subscribe.
| users.{uuid} — only that user may subscribe (e.g. notifications, private updates).
*/

Broadcast::channel('appointments.{uuid}', function ($user, string $uuid) {
    $appointment = Appointments::find($uuid);

    if (!$appointment) {
        return false;
    }

    if ($user->relationLoaded('doctor') ? $user->doctor?->id === $appointment->doctor_id : $user->doctor()->where('id', $appointment->doctor_id)->exists()) {
        return true;
    }

    if ($user->relationLoaded('patient') ? $user->patient?->id === $appointment->patient_id : $user->patient()->where('id', $appointment->patient_id)->exists()) {
        return true;
    }

    return false;
});

Broadcast::channel('users.{uuid}', function ($user, string $uuid) {
    return (string) $user->id === (string) $uuid;
});

Broadcast::channel('video-call.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('messages.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('appointment.{participantId}', function ($user, string $participantId) {
    if ($user->relationLoaded('doctor') ? $user->doctor?->id === $participantId : $user->doctor()->where('id', $participantId)->exists()) {
        return true;
    }

    if ($user->relationLoaded('patient') ? $user->patient?->id === $participantId : $user->patient()->where('id', $participantId)->exists()) {
        return true;
    }

    return false;
});

Broadcast::channel('notifications.{id}', function ($user, string $id) {
    return (string) $user->id === (string) $id;
});