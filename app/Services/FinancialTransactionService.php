<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\Business;
use App\Models\Income;
use App\Models\Expense;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialTransactionService
{
    use WithTransactionalOperations;

    /**
     * Record a financial transaction.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return FinancialTransaction
     * @throws \Exception
     */
    public function recordTransaction(array $data, int $businessId): FinancialTransaction
    {
        return $this->executeTransaction(function () use ($data, $businessId) {
            $transaction = FinancialTransaction::create([
                'business_id' => $businessId,
                'branch_id' => $data['branch_id'] ?? null,
                'type' => $data['type'], // revenue or expense
                'amount' => $data['amount'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now(),
                'category' => $data['category'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Update business balance with explicit assignment for safety
            $business = Business::findOrFail($businessId);
            $newBalance = $data['type'] === 'revenue' 
                ? $business->remainingShopBalance + $data['amount']
                : $business->remainingShopBalance - $data['amount'];
            
            $business->update(['remainingShopBalance' => $newBalance]);

            return $transaction->fresh();
        });
    }

    /**
     * Reconcile transactions for a business within a date range.
     *
     * @param int $businessId
     * @param Carbon $from
     * @param Carbon $to
     * @return array
     */
    public function reconcileTransactions(int $businessId, Carbon $from, Carbon $to): array
    {
        $transactions = FinancialTransaction::where('business_id', $businessId)
            ->whereBetween('transaction_date', [$from, $to])
            ->with(['reference'])
            ->get();

        $totalRevenue = $transactions->where('type', 'revenue')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $netProfit = $totalRevenue - $totalExpense;

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'total_transactions' => $transactions->count(),
                'total_revenue' => $totalRevenue,
                'total_expense' => $totalExpense,
                'net_profit' => $netProfit,
                'profit_margin' => $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0,
            ],
            'transactions' => $transactions,
            'by_category' => $this->groupTransactionsByCategory($transactions),
            'by_payment_method' => $this->groupTransactionsByPaymentMethod($transactions),
        ];
    }

    /**
     * Generate financial report for a business.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function generateFinancialReport(int $businessId, array $filters = []): array
    {
        $query = FinancialTransaction::where('business_id', $businessId);

        // Apply date filters
        if (isset($filters['from_date'])) {
            $query->where('transaction_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('transaction_date', '<=', $filters['to_date']);
        }

        // Apply type filter
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Apply category filter
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Apply branch filter
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        $transactions = $query->with(['reference', 'branch', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_transactions' => $transactions->count(),
                'total_revenue' => $transactions->where('type', 'revenue')->sum('amount'),
                'total_expense' => $transactions->where('type', 'expense')->sum('amount'),
                'net_profit' => $transactions->where('type', 'revenue')->sum('amount') - $transactions->where('type', 'expense')->sum('amount'),
            ],
            'transactions' => $transactions,
            'breakdown' => [
                'by_type' => $this->groupTransactionsByType($transactions),
                'by_category' => $this->groupTransactionsByCategory($transactions),
                'by_month' => $this->groupTransactionsByMonth($transactions),
                'by_branch' => $this->groupTransactionsByBranch($transactions),
            ],
        ];
    }

    /**
     * Get profit and loss statement.
     *
     * @param int $businessId
     * @param Carbon $from
     * @param Carbon $to
     * @return array
     */
    public function getProfitLossStatement(int $businessId, Carbon $from, Carbon $to): array
    {
        $revenue = FinancialTransaction::where('business_id', $businessId)
            ->where('type', 'revenue')
            ->whereBetween('transaction_date', [$from, $to])
            ->get();

        $expenses = FinancialTransaction::where('business_id', $businessId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$from, $to])
            ->get();

        $totalRevenue = $revenue->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $grossProfit = $totalRevenue - $totalExpenses;

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'by_category' => $this->groupTransactionsByCategory($revenue),
                'by_source' => $this->groupTransactionsByReferenceType($revenue),
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'by_category' => $this->groupTransactionsByCategory($expenses),
                'by_type' => $this->groupTransactionsByCategory($expenses),
            ],
            'profitability' => [
                'gross_profit' => $grossProfit,
                'profit_margin' => $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0,
                'expense_ratio' => $totalRevenue > 0 ? ($totalExpenses / $totalRevenue) * 100 : 0,
            ],
        ];
    }

    /**
     * Get cash flow statement.
     *
     * @param int $businessId
     * @param Carbon $from
     * @param Carbon $to
     * @return array
     */
    public function getCashFlowStatement(int $businessId, Carbon $from, Carbon $to): array
    {
        $transactions = FinancialTransaction::where('business_id', $businessId)
            ->whereBetween('transaction_date', [$from, $to])
            ->get();

        $cashInflows = $transactions->where('type', 'revenue')->sum('amount');
        $cashOutflows = $transactions->where('type', 'expense')->sum('amount');
        $netCashFlow = $cashInflows - $cashOutflows;

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'cash_flows' => [
                'inflows' => [
                    'total' => $cashInflows,
                    'by_source' => $this->groupTransactionsByReferenceType($transactions->where('type', 'revenue')),
                ],
                'outflows' => [
                    'total' => $cashOutflows,
                    'by_category' => $this->groupTransactionsByCategory($transactions->where('type', 'expense')),
                ],
                'net' => $netCashFlow,
            ],
            'by_payment_method' => $this->groupTransactionsByPaymentMethod($transactions),
        ];
    }

    /**
     * Group transactions by category.
     *
     * @param Collection $transactions
     * @return Collection
     */
    protected function groupTransactionsByCategory(Collection $transactions): Collection
    {
        return $transactions->groupBy('category')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'transactions' => $group,
            ];
        });
    }

    /**
     * Group transactions by payment method.
     *
     * @param Collection $transactions
     * @return Collection
     */
    protected function groupTransactionsByPaymentMethod(Collection $transactions): Collection
    {
        return $transactions->groupBy('payment_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
            ];
        });
    }

    /**
     * Group transactions by type.
     *
     * @param Collection $transactions
     * @return Collection
     */
    protected function groupTransactionsByType(Collection $transactions): Collection
    {
        return $transactions->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
            ];
        });
    }

    /**
     * Group transactions by month.
     *
     * @param Collection $transactions
     * @return Collection
     */
    protected function groupTransactionsByMonth(Collection $transactions): Collection
    {
        return $transactions->groupBy(function ($transaction) {
            return Carbon::parse($transaction->transaction_date)->format('Y-m');
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'revenue' => $group->where('type', 'revenue')->sum('amount'),
                'expense' => $group->where('type', 'expense')->sum('amount'),
            ];
        });
    }

    /**
     * Group transactions by branch.
     *
     * @param Collection $transactions
     * @return Collection
     */
    protected function groupTransactionsByBranch(Collection $transactions): Collection
    {
        return $transactions->groupBy('branch_id')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'branch_name' => $group->first()->branch->name ?? 'Main',
            ];
        });
    }

    /**
     * Group transactions by reference type.
     *
     * @param Collection $transactions
     * @return Collection
     */
    protected function groupTransactionsByReferenceType(Collection $transactions): Collection
    {
        return $transactions->groupBy('reference_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
            ];
        });
    }

    /**
     * Create financial transaction from sale.
     *
     * @param int $saleId
     * @param int $businessId
     * @return FinancialTransaction
     * @throws \Exception
     */
    public function createFromSale(int $saleId, int $businessId): FinancialTransaction
    {
        return $this->executeTransaction(function () use ($saleId, $businessId) {
            $sale = \App\Models\Sale::findOrFail($saleId);
            
            return $this->recordTransaction([
                'type' => 'revenue',
                'amount' => $sale->totalAmount,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'description' => 'Sale #' . $sale->invoiceNumber,
                'transaction_date' => $sale->saleDate ?? now(),
                'category' => 'sales',
                'payment_method' => $sale->paymentType,
                'branch_id' => $sale->branch_id ?? null,
            ], $businessId);
        });
    }

    /**
     * Create financial transaction from purchase.
     *
     * @param int $purchaseId
     * @param int $businessId
     * @return FinancialTransaction
     * @throws \Exception
     */
    public function createFromPurchase(int $purchaseId, int $businessId): FinancialTransaction
    {
        return $this->executeTransaction(function () use ($purchaseId, $businessId) {
            $purchase = \App\Models\Purchase::findOrFail($purchaseId);
            
            return $this->recordTransaction([
                'type' => 'expense',
                'amount' => $purchase->totalAmount,
                'reference_type' => 'purchase',
                'reference_id' => $purchase->id,
                'description' => 'Purchase #' . $purchase->invoiceNumber,
                'transaction_date' => $purchase->purchaseDate ?? now(),
                'category' => 'purchases',
                'payment_method' => $purchase->paymentType,
                'branch_id' => $purchase->branch_id ?? null,
            ], $businessId);
        });
    }

    /**
     * Delete all financial transactions linked to a given purchase.
     */
    public function deleteTransactionFor(\App\Models\Purchase $purchase): bool
    {
        return FinancialTransaction::where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->delete() > 0;
    }
}