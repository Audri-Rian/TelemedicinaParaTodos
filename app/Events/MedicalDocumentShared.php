<?php

namespace App\Events;

use App\Models\MedicalDocument;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MedicalDocumentShared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $queue = 'high';

    public function __construct(private readonly MedicalDocument $document) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("appointments.{$this->document->appointment_id}");
    }

    public function broadcastAs(): string
    {
        return 'medical-document.shared';
    }

    public function broadcastWith(): array
    {
        // Só metadados — sem file_path nem dado clínico; URLs montadas no front e revalidadas no download (LGPD)
        return [
            'id' => $this->document->id,
            'category' => $this->document->category,
            'name' => $this->document->name,
            'file_type' => $this->document->file_type,
            'file_size' => $this->document->file_size,
            'visibility' => $this->document->visibility,
            'created_at' => $this->document->created_at->format('c'),
        ];
    }
}
