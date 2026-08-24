<?php

namespace App\Services;

use App\Models\Party;
use App\Models\SupplierLedger;
use Illuminate\Support\Facades\DB;

class SupplierPaymentService
{
    public function __construct(
        private SupplierLedgerService $ledgerService,
        private FinancialTransactionService $financialTransactionService,
    ) {}

    /**
     * Record a payment to a supplier.
     */
    public function recordPayment(array $data, int $businessId, int $userId): array
    {
        return DB::transaction(function () use ($data, $businessId, $userId) {
            $partyId = $data['party_id'];
            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw new \InvalidArgumentException(__('Payment amount must be greater than zero.'));
            }

            $party = Party::where('id', $partyId)
                ->where('business_id', $businessId)
                ->firstOrFail();

            // Record in supplier ledger
            $ledgerEntry = $this->ledgerService->recordPayment(
                $businessId,
                $partyId,
                $amount,
                $data['invoice_number'] ?? null,
                $userId,
                $data['notes'] ?? null
            );

            // Update Party.due for backward compatibility
            $party->update(['due' => max(0, $party->due - $amount)]);

            // Record financial transaction
            $this->financialTransactionService->recordTransaction([
                'type' => 'expense',
                'amount' => $amount,
                'reference_type' => 'supplier_payment',
                'reference_id' => $ledgerEntry->id,
                'description' => "Payment to supplier {$party->name}",
                'transaction_date' => $data['payment_date'] ?? now(),
                'category' => 'supplier_payments',
                'payment_method' => $data['payment_method'] ?? 'cash',
                'notes' => $data['notes'] ?? null,
            ], $businessId);

            // Audit log
            AuditLogger::log('supplier_payment', "Paid {$amount} to supplier {$party->name}", [
                'party_id' => $partyId,
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
            ]);

            return [
                'ledger_entry' => $ledgerEntry,
                'new_balance' => $ledgerEntry->balance_after,
                'party_due' => $party->fresh()->due,
            ];
        });
    }

    /**
     * Get payment history for a supplier.
     */
    public function getPaymentHistory(int $businessId, int $partyId, int $limit = 50)
    {
        return SupplierLedger::where('business_id', $businessId)
            ->where('party_id', $partyId)
            ->where('transaction_type', SupplierLedger::TYPE_PAYMENT)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
