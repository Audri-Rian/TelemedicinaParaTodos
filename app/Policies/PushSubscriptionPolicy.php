<?php

namespace App\Policies;

use App\Models\PushSubscription;
use App\Models\User;

class PushSubscriptionPolicy
{
    public function view(User $user, PushSubscription $pushSubscription): bool
    {
        return $this->ownsSubscription($user, $pushSubscription);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PushSubscription $pushSubscription): bool
    {
        return $this->ownsSubscription($user, $pushSubscription);
    }

    public function delete(User $user, PushSubscription $pushSubscription): bool
    {
        return $this->ownsSubscription($user, $pushSubscription);
    }

    private function ownsSubscription(User $user, PushSubscription $pushSubscription): bool
    {
        return (string) $user->id === (string) $pushSubscription->user_id;
    }
}
