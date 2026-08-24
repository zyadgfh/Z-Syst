<?php

namespace App\Mail;

use App\Models\LoyaltyPoint;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoyaltyPointsExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $totalExpiringPoints,
        public int $daysUntilExpiry,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', 'noreply@zsyst.com');
        $fromName = config('mail.from.name', 'Z-Syst');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: '⏰ نقاط ولائك على وشك الانتهاء — ' . $this->totalExpiringPoints . ' نقطة',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.loyalty-points-expiring',
            with: [
                'user'                  => $this->user,
                'totalExpiringPoints'   => $this->totalExpiringPoints,
                'daysUntilExpiry'       => $this->daysUntilExpiry,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
