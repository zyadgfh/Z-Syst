<?php

namespace App\Notifications;

use App\Models\StockTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * StockTransferRejectedNotification
 * 
 * Notification sent when a stock transfer is rejected.
 */
class StockTransferRejectedNotification extends Notification
{
    use Queueable;

    /**
     * The stock transfer instance.
     *
     * @var \App\Models\StockTransfer
     */
    protected StockTransfer $transfer;

    /**
     * Create a new notification instance.
     *
     * @param  \App\Models\StockTransfer  $transfer
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
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Stock Transfer Rejected - ' . $this->transfer->transfer_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your stock transfer request has been rejected.')
            ->line('Transfer Number: ' . $this->transfer->transfer_number)
            ->line('From: ' . $this->transfer->fromBranch->name)
            ->line('To: ' . $this->transfer->toBranch->name)
            ->line('Rejection Reason: ' . ($this->transfer->rejection_reason ?? 'No reason provided'))
            ->action('View Transfer', url('/api/v1/stock-transfers/' . $this->transfer->id))
            ->line('Please review the rejection reason and contact the approver if needed.')
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
            'rejection_reason' => $this->transfer->rejection_reason,
            'message' => "Stock transfer {$this->transfer->transfer_number} has been rejected.",
        ];
    }
}
