<?php

namespace App\Listeners;

use App\Events\StockTransferShipped;
use App\Notifications\StockTransferShippedNotification;

/**
 * SendStockTransferShippedNotification Listener
 * 
 * Sends notification when a stock transfer is shipped.
 */
class SendStockTransferShippedNotification
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\StockTransferShipped  $event
     * @return void
     */
    public function handle(StockTransferShipped $event): void
    {
        $transfer = $event->transfer;
        
        // Notify destination branch users
        $this->notifyBranchUsers($transfer->to_branch_id, $transfer);
    }

    /**
     * Notify users at a specific branch.
     *
     * @param int $branchId
     * @param \App\Models\StockTransfer $transfer
     * @return void
     */
    protected function notifyBranchUsers(int $branchId, $transfer): void
    {
        $users = \App\Models\User::where('branch_id', $branchId)
            ->where('company_id', $transfer->company_id)
            ->where('status', 'active')
            ->get();

        foreach ($users as $user) {
            if ($user->hasPermission('receive_stock_transfers')) {
                $user->notify(new StockTransferShippedNotification($transfer));
            }
        }
    }
}
