<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * Sender or receiver may view a message.
     */
    public function view(User $user, Message $message): bool
    {
        return $this->isParticipant($user, $message);
    }

    /**
     * Authenticated users may create messages; the actual sender↔receiver
     * relationship is enforced by ConversationPolicy::sendMessage on the
     * receiver_id submitted in the request.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the sender may edit a message, and only while it has not been
     * delivered or read by the receiver.
     */
    public function update(User $user, Message $message): bool
    {
        if ((string) $user->id !== (string) $message->sender_id) {
            return false;
        }

        return $message->read_at === null
            && $message->status !== Message::STATUS_DELIVERED;
    }

    /**
     * Only the sender may delete (soft delete) a message.
     */
    public function delete(User $user, Message $message): bool
    {
        return (string) $user->id === (string) $message->sender_id;
    }

    /**
     * Only the receiver can mark a message as delivered.
     */
    public function markAsDelivered(User $user, Message $message): bool
    {
        return (string) $user->id === (string) $message->receiver_id;
    }

    /**
     * Only the receiver can mark a message as read.
     */
    public function markAsRead(User $user, Message $message): bool
    {
        return (string) $user->id === (string) $message->receiver_id;
    }

    private function isParticipant(User $user, Message $message): bool
    {
        return (string) $user->id === (string) $message->sender_id
            || (string) $user->id === (string) $message->receiver_id;
    }
}
