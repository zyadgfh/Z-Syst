<?php

namespace App\Mail;

use App\Models\CustomerOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomerOrder $order
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', 'noreply@zsyst.com');
        $fromName = config('mail.from.name', 'Z-Syst');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: __('Order Confirmed') . ' - ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order-confirmation',
            with: [
                'order' => $this->order,
                'items' => $this->order->items,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
