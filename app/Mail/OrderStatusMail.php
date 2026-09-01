<?php

namespace App\Mail;

use App\Models\CustomerOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomerOrder $order,
        public string $previousStatus
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', 'noreply@zsyst.com');
        $fromName = config('mail.from.name', 'Z-Syst');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: __('Order Update') . ' - ' . $this->order->order_number . ' - ' . ucfirst($this->order->status),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order-status-update',
            with: [
                'order' => $this->order,
                'previousStatus' => $this->previousStatus,
                'items' => $this->order->items,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
