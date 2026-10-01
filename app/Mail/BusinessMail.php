<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic, reusable business notification email.
 *
 * Used by the NotificationService for supplier updates (purchase orders,
 * supplier invoices), doctor attention alerts and any other transactional
 * message that needs a subject, a few lines of text and an optional
 * items table.
 */
class BusinessMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  string  $subjectLine  Email subject.
     * @param  array  $lines  Plain text paragraphs rendered in the body.
     * @param  array|null  $table  Optional table: ['headers' => [...], 'rows' => [[...], ...]].
     * @param  string|null  $footerNote  Optional footer note.
     */
    public function __construct(
        public string $subjectLine,
        public array $lines = [],
        public ?array $table = null,
        public ?string $footerNote = null,
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.business',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
