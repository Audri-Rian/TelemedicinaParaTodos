<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * Only the receiver of the message can mark it as delivered.
     */
    public function markAsDelivered(User $user, Message $message): bool
    {
        return (string) $user->id === (string) $message->receiver_id;
    }
}
