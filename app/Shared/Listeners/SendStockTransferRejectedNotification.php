<?php

namespace App\Listeners;

use App\Events\StockTransferRejected;
use App\Notifications\StockTransferRejectedNotification;

/**
 * SendStockTransferRejectedNotification Listener
 *
 * Sends notification when a stock transfer is rejected.
 */
class SendStockTransferRejectedNotification
{
    /**
     * Handle the event.
     */
    public function handle(StockTransferRejected $event): void
    {
        $transfer = $event->transfer;

        // Notify the requester
        if ($transfer->requestedBy) {
            $transfer->requestedBy->notify(new StockTransferRejectedNotification($transfer));
        }
    }
}
