<?php
 
use Illuminate\Support\Facades\Broadcast;
 
// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });
 
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