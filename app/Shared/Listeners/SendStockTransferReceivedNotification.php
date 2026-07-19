<?php

namespace App\Listeners;

use App\Events\StockTransferReceived;
use App\Models\StockTransfer;
use App\Models\User;
use App\Notifications\StockTransferReceivedNotification;

/**
 * SendStockTransferReceivedNotification Listener
 *
 * Sends notification when a stock transfer is received.
 */
class SendStockTransferReceivedNotification
{
    /**
     * Handle the event.
     */
    public function handle(StockTransferReceived $event): void
    {
        $transfer = $event->transfer;

        // Notify the source branch
        $this->notifyBranchUsers($transfer->from_branch_id, $transfer);

        // Notify the requester
        if ($transfer->requestedBy) {
            $transfer->requestedBy->notify(new StockTransferReceivedNotification($transfer));
        }
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
            if ($user->hasPermission('manage_stock_transfers')) {
                $user->notify(new StockTransferReceivedNotification($transfer));
            }
        }
    }
}
