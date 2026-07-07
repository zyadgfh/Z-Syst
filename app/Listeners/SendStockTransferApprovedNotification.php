<?php

namespace App\Listeners;

use App\Events\StockTransferApproved;
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
     *
     * @param  \App\Events\StockTransferApproved  $event
     * @return void
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
                $user->notify(new StockTransferApprovedNotification($transfer));
            }
        }
    }
}
