<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\PushNotificationSender;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NullPushSender implements PushNotificationSender
{
    public function send(Notification $notification): void
    {
        Log::debug('Push notification skipped because push is disabled', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'driver' => 'null',
        ]);
    }
}
