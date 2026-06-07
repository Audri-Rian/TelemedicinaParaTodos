<?php

namespace App\Observers;

use App\Models\NotificationPreference;
use App\Services\NotificationService;

class NotificationPreferenceObserver
{
    public function saved(NotificationPreference $preference): void
    {
        NotificationService::forgetPreferenceCache($preference->user_id, $preference->channel);
    }

    public function deleted(NotificationPreference $preference): void
    {
        NotificationService::forgetPreferenceCache($preference->user_id, $preference->channel);
    }
}
