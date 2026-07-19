<?php

namespace App\Listeners;

use App\Events\StockTransferShipped;
use App\Models\StockTransfer;
use App\Models\User;
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
     * @param  StockTransfer  $transfer
     */
    protected function notifyBranchUsers(int $branchId, $transfer): void
    {
        $users = User::where('branch_id', $branchId)
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
