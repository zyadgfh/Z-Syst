<?php

namespace App\Listeners;

use App\Events\StockTransferCancelled;
use App\Models\StockTransfer;
use App\Models\User;
use App\Notifications\StockTransferCancelledNotification;

/**
 * SendStockTransferCancelledNotification Listener
 *
 * Sends notification when a stock transfer is cancelled.
 */
class SendStockTransferCancelledNotification
{
    /**
     * Handle the event.
     */
    public function handle(StockTransferCancelled $event): void
    {
        $transfer = $event->transfer;

        // Notify the requester (if not the one who cancelled)
        if ($transfer->requestedBy && $transfer->requested_by !== auth()->id()) {
            $transfer->requestedBy->notify(new StockTransferCancelledNotification($transfer));
        }

        // Notify the approver (if any)
        if ($transfer->approvedBy && $transfer->approved_by !== auth()->id()) {
            $transfer->approvedBy->notify(new StockTransferCancelledNotification($transfer));
        }

        // Notify source branch users
        $this->notifyBranchUsers($transfer->from_branch_id, $transfer);

        // Notify destination branch users
        $this->notifyBranchUsers($transfer->to_branch_id, $transfer);
    }

    /**
     * Notify users at a specific branch.
     */
    protected function notifyBranchUsers(int $branchId, StockTransfer $transfer): void
    {
        $users = User::where('branch_id', $branchId)
            ->where('company_id', $transfer->company_id)
            ->where('status', 'active')
            ->get();

        foreach ($users as $user) {
            if ($user->hasPermission('manage_stock_transfers') || $user->hasPermission('view_stock_transfers')) {
                $user->notify(new StockTransferCancelledNotification($transfer));
            }
        }
    }
}