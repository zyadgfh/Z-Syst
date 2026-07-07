<?php

namespace App\Events;

use App\Models\StockTransfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * StockTransferReceived Event
 * 
 * Fired when a stock transfer is received.
 * Triggers notifications to the source branch and updates analytics.
 */
class StockTransferReceived
{
    use Dispatchable, SerializesModels;

    /**
     * The stock transfer instance.
     *
     * @var \App\Models\StockTransfer
     */
    public StockTransfer $transfer;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\StockTransfer  $transfer
     * @return void
     */
    public function __construct(StockTransfer $transfer)
    {
        $this->transfer = $transfer;
    }
}
