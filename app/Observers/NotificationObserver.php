<?php

namespace App\Observers;

use App\Models\Notification;
use App\Services\NotificationService;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        NotificationService::forgetUnreadCountCache($notification->user_id);
    }

    public function updated(Notification $notification): void
    {
        if ($notification->wasChanged('read_at')) {
            NotificationService::forgetUnreadCountCache($notification->user_id);
        }
    }

    public function deleted(Notification $notification): void
    {
        NotificationService::forgetUnreadCountCache($notification->user_id);
    }
}
