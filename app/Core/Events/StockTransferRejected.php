<?php

namespace App\Events;

use App\Models\StockTransfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * StockTransferRejected Event
 *
 * Fired when a stock transfer is rejected.
 * Triggers notifications to the requester.
 */
class StockTransferRejected
{
    use Dispatchable, SerializesModels;

    /**
     * The stock transfer instance.
     */
    public StockTransfer $transfer;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(StockTransfer $transfer)
    {
        $this->transfer = $transfer;
    }
}
