<?php

namespace App\Services;

use App\Models\SupplierLedger;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;

class SupplierLedgerService
{
    /**
     * Record a purchase transaction in the supplier ledger.
     */
    public function recordPurchase(Purchase $purchase, int $userId): SupplierLedger
    {
        $partyId = $purchase->party_id;
        if (!$partyId) {
            return null;
        }

        $businessId = $purchase->business_id;
        $debitAmount = (float) $purchase->totalAmount;

        // Get current balance
        $currentBalance = SupplierLedger::getBalance($businessId, $partyId);
        $newBalance = $currentBalance + $debitAmount;

        return SupplierLedger::create([
            'business_id' => $businessId,
            'party_id' => $partyId,
            'user_id' => $userId,
            'transaction_type' => SupplierLedger::TYPE_PURCHASE,
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'invoice_number' => $purchase->invoiceNumber,
            'debit' => $debitAmount,
            'credit' => 0,
            'balance_after' => $newBalance,
            'description' => "Purchase invoice #{$purchase->invoiceNumber}",
            'notes' => $purchase->note,
        ]);
    }

    /**
     * Record a purchase return in the supplier ledger.
     */
    public function recordPurchaseReturn(PurchaseReturn $purchaseReturn, float $creditAmount, int $userId): SupplierLedger
    {
        $purchase = $purchaseReturn->purchase;
        $partyId = $purchaseReturn->party_id ?? $purchase->party_id;
        if (!$partyId) {
            return null;
        }

        $businessId = $purchaseReturn->business_id;

        // Get current balance
        $currentBalance = SupplierLedger::getBalance($businessId, $partyId);
        $newBalance = $currentBalance - $creditAmount;

        return SupplierLedger::create([
            'business_id' => $businessId,
            'party_id' => $partyId,
            'user_id' => $purchaseReturn->user_id,
            'transaction_type' => SupplierLedger::TYPE_PURCHASE_RETURN,
            'reference_type' => PurchaseReturn::class,
            'reference_id' => $purchaseReturn->id,
            'invoice_number' => $purchaseReturn->invoice_no,
            'debit' => 0,
            'credit' => $creditAmount,
            'balance_after' => $newBalance,
            'description' => "Return for purchase #{$purchase->invoiceNumber}",
            'notes' => $purchaseReturn->reason,
        ]);
    }

    /**
     * Record a payment to supplier in the ledger.
     */
    public function recordPayment(int $businessId, int $partyId, float $amount, ?string $invoiceNumber, int $userId, ?string $notes = null): SupplierLedger
    {
        $currentBalance = SupplierLedger::getBalance($businessId, $partyId);
        $newBalance = $currentBalance - $amount;

        return SupplierLedger::create([
            'business_id' => $businessId,
            'party_id' => $partyId,
            'user_id' => $userId,
            'transaction_type' => SupplierLedger::TYPE_PAYMENT,
            'reference_type' => null,
            'reference_id' => null,
            'invoice_number' => $invoiceNumber,
            'debit' => 0,
            'credit' => $amount,
            'balance_after' => $newBalance,
            'description' => $invoiceNumber ? "Payment for invoice #{$invoiceNumber}" : 'Payment to supplier',
            'notes' => $notes,
        ]);
    }

    /**
     * Record a balance adjustment.
     */
    public function recordAdjustment(int $businessId, int $partyId, float $debit, float $credit, string $description, int $userId, ?string $notes = null): SupplierLedger
    {
        $currentBalance = SupplierLedger::getBalance($businessId, $partyId);
        $newBalance = $currentBalance + $debit - $credit;

        return SupplierLedger::create([
            'business_id' => $businessId,
            'party_id' => $partyId,
            'user_id' => $userId,
            'transaction_type' => SupplierLedger::TYPE_ADJUSTMENT,
            'reference_type' => null,
            'reference_id' => null,
            'invoice_number' => null,
            'debit' => $debit,
            'credit' => $credit,
            'balance_after' => $newBalance,
            'description' => $description,
            'notes' => $notes,
        ]);
    }

    /**
     * Reverse a purchase ledger entry (for cancellation).
     */
    public function reversePurchase(Purchase $purchase, int $userId): SupplierLedger
    {
        $partyId = $purchase->party_id;
        if (!$partyId) {
            return null;
        }

        $businessId = $purchase->business_id;
        $creditAmount = (float) $purchase->totalAmount;

        // Get current balance
        $currentBalance = SupplierLedger::getBalance($businessId, $partyId);
        $newBalance = $currentBalance - $creditAmount;

        return SupplierLedger::create([
            'business_id' => $businessId,
            'party_id' => $partyId,
            'user_id' => $userId,
            'transaction_type' => SupplierLedger::TYPE_PURCHASE,
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'invoice_number' => $purchase->invoiceNumber,
            'debit' => 0,
            'credit' => $creditAmount,
            'balance_after' => $newBalance,
            'description' => "Reversal of purchase invoice #{$purchase->invoiceNumber} (cancelled)",
            'notes' => 'Purchase cancelled',
        ]);
    }

    /**
     * Reverse a purchase return ledger entry (for cancellation).
     */
    public function reversePurchaseReturn(PurchaseReturn $purchaseReturn, float $creditAmount, int $userId): SupplierLedger
    {
        $purchase = $purchaseReturn->purchase;
        $partyId = $purchaseReturn->party_id ?? $purchase->party_id;
        if (!$partyId) {
            return null;
        }

        $businessId = $purchaseReturn->business_id;

        // Get current balance
        $currentBalance = SupplierLedger::getBalance($businessId, $partyId);
        $newBalance = $currentBalance + $creditAmount;

        return SupplierLedger::create([
            'business_id' => $businessId,
            'party_id' => $partyId,
            'user_id' => $userId,
            'transaction_type' => SupplierLedger::TYPE_PURCHASE_RETURN,
            'reference_type' => PurchaseReturn::class,
            'reference_id' => $purchaseReturn->id,
            'invoice_number' => $purchaseReturn->invoice_no,
            'debit' => $creditAmount,
            'credit' => 0,
            'balance_after' => $newBalance,
            'description' => "Reversal of return #{$purchaseReturn->invoice_no} (cancelled)",
            'notes' => 'Purchase return cancelled',
        ]);
    }

    /**
     * Get the full ledger for a supplier.
     */
    public function getLedger(int $businessId, int $partyId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return SupplierLedger::where('business_id', $businessId)
            ->where('party_id', $partyId)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the current balance for a supplier.
     */
    public function getBalance(int $businessId, int $partyId): float
    {
        return SupplierLedger::getBalance($businessId, $partyId);
    }

    /**
     * Get ledger summary for a supplier.
     */
    public function getSummary(int $businessId, int $partyId): array
    {
        $ledger = SupplierLedger::where('business_id', $businessId)
            ->where('party_id', $partyId)
            ->get();

        return [
            'current_balance' => SupplierLedger::getBalance($businessId, $partyId),
            'total_purchases' => $ledger->where('transaction_type', SupplierLedger::TYPE_PURCHASE)->sum('debit'),
            'total_returns' => $ledger->where('transaction_type', SupplierLedger::TYPE_PURCHASE_RETURN)->sum('credit'),
            'total_payments' => $ledger->where('transaction_type', SupplierLedger::TYPE_PAYMENT)->sum('credit'),
            'total_adjustments' => $ledger->where('transaction_type', SupplierLedger::TYPE_ADJUSTMENT)->sum('debit') - $ledger->where('transaction_type', SupplierLedger::TYPE_ADJUSTMENT)->sum('credit'),
            'transaction_count' => $ledger->count(),
        ];
    }
}
