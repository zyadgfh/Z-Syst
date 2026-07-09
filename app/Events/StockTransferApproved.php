<?php

namespace App\Events;

use App\Models\StockTransfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * StockTransferApproved Event
 *
 * Fired when a stock transfer is approved.
 * Triggers notifications to relevant users.
 */
class StockTransferApproved
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
