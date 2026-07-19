<?php

namespace App\Listeners;

use App\Events\StockTransferApproved;
use App\Models\StockTransfer;
use App\Models\User;
use App\Notifications\StockTransferApprovedNotification;

/**
 * SendStockTransferApprovedNotification Listener
 *
 * Sends notification when a stock transfer is approved.
 */
class SendStockTransferApprovedNotification
{
    /**
     * Handle the event.
     */
    public function handle(StockTransferApproved $event): void
    {
        $transfer = $event->transfer;

        // Notify the requester
        if ($transfer->requestedBy) {
            $transfer->requestedBy->notify(new StockTransferApprovedNotification($transfer));
        }

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
                $user->notify(new StockTransferApprovedNotification($transfer));
            }
        }
    }
}
