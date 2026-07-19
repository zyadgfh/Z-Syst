<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class StockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Product $product,
        private readonly int $currentStock,
        private readonly int $alertQty
    ) {
    }

    /**
     * Get the notification channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'stock_alert',
            'product_id' => $this->product->id,
            'product_name' => $this->product->productName,
            'current_stock' => $this->currentStock,
            'alert_qty' => $this->alertQty,
            'severity' => $this->getSeverity(),
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast($notifiable): array
    {
        return [
            'type' => 'stock_alert',
            'product_id' => $this->product->id,
            'product_name' => $this->product->productName,
            'current_stock' => $this->currentStock,
            'alert_qty' => $this->alertQty,
            'severity' => $this->getSeverity(),
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get severity level based on stock ratio.
     */
    private function getSeverity(): string
    {
        if ($this->currentStock <= 0) {
            return 'critical';
        }

        if ($this->currentStock <= $this->alertQty * 0.5) {
            return 'warning';
        }

        return 'info';
    }
}