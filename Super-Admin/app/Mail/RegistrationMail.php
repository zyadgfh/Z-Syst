<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    private $code;

    public function __construct($code)
    {
        $this->code = $code;
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromAddress = env('MAIL_FROM_ADDRESS', 'default@example.com');
        $fromName = env('MAIL_FROM_NAME', 'Default Name');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: 'Welcome Mail - Your registration has been created successfully.',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.registration',
            with: [
                'code' => $this->code,
            ],
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
