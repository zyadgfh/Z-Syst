<?php

namespace App\Mail;

use App\Models\RecallEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecallNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecallEvent $recall,
        public array $context,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('🚨 Important Drug Recall Notice — :product', [
                'product' => $this->context['product_name'],
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recall-notification',
            with: [
                'recall' => $this->recall,
                'context' => $this->context,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
