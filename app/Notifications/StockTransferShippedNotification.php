<?php

namespace App\Notifications;

use App\Models\StockTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * StockTransferShippedNotification
 *
 * Notification sent when a stock transfer is shipped.
 */
class StockTransferShippedNotification extends Notification
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
            ->subject('Stock Transfer Shipped - '.$this->transfer->transfer_number)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A stock transfer has been shipped to your branch.')
            ->line('Transfer Number: '.$this->transfer->transfer_number)
            ->line('From: '.$this->transfer->fromBranch->name)
            ->line('To: '.$this->transfer->toBranch->name)
            ->line('Total Items: '.$this->transfer->total_items)
            ->line('Total Quantity: '.$this->transfer->total_quantity)
            ->line('Shipped At: '.$this->transfer->shipped_at->format('Y-m-d H:i:s'))
            ->action('View Transfer', url('/api/v1/stock-transfers/'.$this->transfer->id))
            ->line('Please prepare to receive the items.')
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
            'shipped_at' => $this->transfer->shipped_at,
            'message' => "Stock transfer {$this->transfer->transfer_number} has been shipped to your branch.",
        ];
    }
}
