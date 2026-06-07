<?php

namespace App\Contracts\Notifications;

use App\Models\Notification;

interface PushNotificationSender
{
    public function send(Notification $notification): void;
}
