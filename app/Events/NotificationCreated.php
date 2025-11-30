<?php

namespace App\Events;

use App\Models\Notification;
use App\Presenters\NotificationPresenter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Notification $notification)
    {
        $this->notification->loadMissing('user');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("notifications.{$this->notification->user_id}"),
        ];
    }

    /**
     * Nome do evento para broadcast
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Dados a serem enviados no broadcast
     */
    public function broadcastWith(): array
    {
        $presenter = new NotificationPresenter();
        return $presenter->transform($this->notification);
    }
}
