<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GeneralLedgerService
{
    /**
     * Get the General Ledger entries for a specific business.
     *
     * @param int $businessId
     * @param array $filters
     * @return array
     */
    public function getLedger(int $businessId, array $filters = []): array
    {
        $query = FinancialTransaction::where('business_id', $businessId);

        // Apply filters
        if (!empty($filters['from_date'])) {
            $query->whereDate('transaction_date', '>=', Carbon::parse($filters['from_date']));
        }
        
        if (!empty($filters['to_date'])) {
            $query->whereDate('transaction_date', '<=', Carbon::parse($filters['to_date']));
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Get transactions ordered chronologically
        $transactions = $query->with(['reference', 'user', 'branch'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate running balance
        // We start with the business opening balance if we are not filtering by date,
        // otherwise we should ideally calculate the balance up to the from_date.
        // For simplicity, we calculate a running total for the fetched transactions.
        
        $runningBalance = 0;
        if (!empty($filters['from_date'])) {
            $previousRevenue = FinancialTransaction::where('business_id', $businessId)
                ->whereDate('transaction_date', '<', Carbon::parse($filters['from_date']))
                ->where('type', 'revenue')
                ->sum('amount');
                
            $previousExpense = FinancialTransaction::where('business_id', $businessId)
                ->whereDate('transaction_date', '<', Carbon::parse($filters['from_date']))
                ->where('type', 'expense')
                ->sum('amount');
                
            $runningBalance = $previousRevenue - $previousExpense;
        }

        $ledgerEntries = $transactions->map(function ($transaction) use (&$runningBalance) {
            if ($transaction->type === 'revenue') {
                $runningBalance += $transaction->amount;
            } else {
                $runningBalance -= $transaction->amount;
            }

            return [
                'id' => $transaction->id,
                'date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
                'description' => $transaction->description,
                'category' => $transaction->category,
                'reference_type' => $transaction->reference_type,
                'reference_id' => $transaction->reference_id,
                'payment_method' => $transaction->payment_method,
                'debit' => $transaction->type === 'expense' ? $transaction->amount : 0,
                'credit' => $transaction->type === 'revenue' ? $transaction->amount : 0,
                'balance' => $runningBalance,
                'user' => $transaction->user ? $transaction->user->name : 'System',
                'branch' => $transaction->branch ? $transaction->branch->name : 'Main',
            ];
        });

        $totalDebit = $transactions->where('type', 'expense')->sum('amount');
        $totalCredit = $transactions->where('type', 'revenue')->sum('amount');

        return [
            'summary' => [
                'opening_balance' => $runningBalance - ($totalCredit - $totalDebit),
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'closing_balance' => $runningBalance,
                'net_movement' => $totalCredit - $totalDebit,
            ],
            'entries' => $ledgerEntries,
            'filters' => $filters,
        ];
    }
}
