<?php

namespace App\Mail;

use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IntegrationFailureMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PartnerIntegration $partner,
        public IntegrationEvent $integrationEvent,
        public string $sanitizedError
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Integração] Falha - '.$this->partner->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'integrations.failure-alert',
            with: [
                'partner' => $this->partner,
                'integrationEvent' => $this->integrationEvent,
                'sanitizedError' => $this->sanitizedError,
            ],
        );
    }
}
