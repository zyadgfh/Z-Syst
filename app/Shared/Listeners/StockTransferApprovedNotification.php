<?php

namespace App\Notifications;

use App\Models\StockTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * StockTransferApprovedNotification
 *
 * Notification sent when a stock transfer is approved.
 */
class StockTransferApprovedNotification extends Notification
{
    use Queueable;

    /**
     * The stock transfer instance.
     */
    protected StockTransfer $transfer;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(StockTransfer $transfer)
    {
        $this->transfer = $transfer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Stock Transfer Approved - '.$this->transfer->transfer_number)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your stock transfer request has been approved.')
            ->line('Transfer Number: '.$this->transfer->transfer_number)
            ->line('From: '.$this->transfer->fromBranch->name)
            ->line('To: '.$this->transfer->toBranch->name)
            ->line('Total Items: '.$this->transfer->total_items)
            ->line('Total Quantity: '.$this->transfer->total_quantity)
            ->line('Total Value: '.number_format($this->transfer->total_value, 2))
            ->action('View Transfer', url('/api/v1/stock-transfers/'.$this->transfer->id))
            ->line('Please proceed with shipping the items.')
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'transfer_id' => $this->transfer->id,
            'transfer_number' => $this->transfer->transfer_number,
            'from_branch' => $this->transfer->fromBranch->name,
            'to_branch' => $this->transfer->toBranch->name,
            'status' => $this->transfer->status,
            'total_items' => $this->transfer->total_items,
            'total_quantity' => $this->transfer->total_quantity,
            'total_value' => $this->transfer->total_value,
            'message' => "Stock transfer {$this->transfer->transfer_number} has been approved.",
        ];
    }
}
