<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        // Carregar relacionamentos necessários
        $this->message->loadMissing([
            'sender:id,name,avatar_path',
            'receiver:id,name,avatar_path',
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Broadcast para ambos os usuários da conversa
        return [
            new PrivateChannel("messages.{$this->message->sender_id}"),
            new PrivateChannel("messages.{$this->message->receiver_id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'content' => $this->message->content,
            'read_at' => $this->message->read_at?->format('c'),
            'status' => $this->message->status,
            'delivered_at' => $this->message->delivered_at?->format('c'),
            'created_at' => $this->message->created_at->format('c'),
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'avatar_path' => $this->message->sender->avatar_path,
            ],
            'receiver' => [
                'id' => $this->message->receiver->id,
                'name' => $this->message->receiver->name,
                'avatar_path' => $this->message->receiver->avatar_path,
            ],
        ];
    }
}
