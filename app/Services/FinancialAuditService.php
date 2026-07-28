<?php

namespace App\Services;

use App\Models\FinancialAuditLog;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialAuditService
{
    /**
     * Create a new financial audit.
     *
     * @param array $data
     * @return FinancialAuditLog
     */
    public function createAudit(array $data): FinancialAuditLog
    {
        $data['audit_number'] = $this->generateAuditNumber($data['business_id']);
        $data['status'] = 'pending';
        
        return FinancialAuditLog::create($data);
    }

    /**
     * Generate a unique financial audit number.
     *
     * @param int $businessId
     * @return string
     */
    private function generateAuditNumber(int $businessId): string
    {
        $prefix = 'FIN-AUD-' . date('Ymd') . '-';
        $lastAudit = FinancialAuditLog::where('business_id', $businessId)
            ->where('audit_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastAudit ? (int)substr($lastAudit->audit_number, -4) + 1 : 1;
        
        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Start a financial audit.
     *
     * @param FinancialAuditLog $audit
     * @param int $userId
     * @return FinancialAuditLog
     */
    public function startAudit(FinancialAuditLog $audit, int $userId): FinancialAuditLog
    {
        $audit->update([
            'status' => 'in_progress',
            'user_id' => $userId,
        ]);

        return $audit->fresh();
    }

    /**
     * Calculate financial data for the audit period.
     *
     * @param FinancialAuditLog $audit
     * @return array
     */
    public function calculateFinancialData(FinancialAuditLog $audit): array
    {
        $startDate = $audit->start_date;
        $endDate = $audit->end_date;
        $businessId = $audit->business_id;

        // Calculate total revenue from sales
        $totalSales = Sale::where('business_id', $businessId)
            ->whereBetween('saleDate', [$startDate, $endDate])
            ->sum('totalAmount');

        // Calculate total revenue from income records
        $totalIncome = Income::where('business_id', $businessId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $totalRevenue = $totalSales + $totalIncome;

        // Calculate total expenses
        $totalExpenses = Expense::where('business_id', $businessId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Calculate purchases (should be considered in cost of goods, not expenses)
        $totalPurchases = Purchase::where('business_id', $businessId)
            ->whereBetween('purchaseDate', [$startDate, $endDate])
            ->sum('totalAmount');

        return [
            'total_sales' => $totalSales,
            'total_income' => $totalIncome,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'total_purchases' => $totalPurchases,
        ];
    }

    /**
     * Execute a financial audit with calculated data.
     *
     * @param FinancialAuditLog $audit
     * @param float $openingBalance
     * @param float $closingBalance
     * @return FinancialAuditLog
     */
    public function executeAudit(FinancialAuditLog $audit, float $openingBalance, float $closingBalance): FinancialAuditLog
    {
        $financialData = $this->calculateFinancialData($audit);

        $audit->update([
            'status' => 'in_progress',
            'opening_balance' => $openingBalance,
            'total_revenue' => $financialData['total_revenue'],
            'total_expenses' => $financialData['total_expenses'],
            'closing_balance' => $closingBalance,
        ]);

        $audit->calculateVariance();
        $audit->save();

        return $audit->fresh();
    }

    /**
     * Complete a financial audit.
     *
     * @param FinancialAuditLog $audit
     * @return FinancialAuditLog
     */
    public function completeAudit(FinancialAuditLog $audit): FinancialAuditLog
    {
        $audit->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $audit->fresh();
    }

    /**
     * Cancel a financial audit.
     *
     * @param FinancialAuditLog $audit
     * @param string|null $reason
     * @return FinancialAuditLog
     */
    public function cancelAudit(FinancialAuditLog $audit, ?string $reason = null): FinancialAuditLog
    {
        $audit->update([
            'status' => 'cancelled',
            'notes' => $reason ? $audit->notes . ' - Cancelled: ' . $reason : $audit->notes . ' - Cancelled',
        ]);

        return $audit->fresh();
    }

    /**
     * Get audit report with detailed breakdown.
     *
     * @param FinancialAuditLog $audit
     * @return array
     */
    public function getAuditReport(FinancialAuditLog $audit): array
    {
        $financialData = $this->calculateFinancialData($audit);
        $expectedClosingBalance = $audit->opening_balance + $audit->total_revenue - $audit->total_expenses;
        $variance = $audit->closing_balance - $expectedClosingBalance;

        return [
            'audit_info' => [
                'audit_number' => $audit->audit_number,
                'audit_type' => $audit->audit_type,
                'period' => [
                    'start_date' => $audit->start_date->format('Y-m-d'),
                    'end_date' => $audit->end_date->format('Y-m-d'),
                ],
                'status' => $audit->status,
            ],
            'financial_summary' => [
                'opening_balance' => $audit->opening_balance,
                'total_sales' => $financialData['total_sales'],
                'total_other_income' => $financialData['total_income'],
                'total_revenue' => $audit->total_revenue,
                'total_expenses' => $audit->total_expenses,
                'total_purchases' => $financialData['total_purchases'],
                'closing_balance' => $audit->closing_balance,
                'expected_closing_balance' => $expectedClosingBalance,
                'variance' => $variance,
            ],
            'variance_analysis' => [
                'is_balanced' => abs($variance) < 0.01,
                'variance_percentage' => $expectedClosingBalance > 0 ? ($variance / $expectedClosingBalance) * 100 : 0,
                'requires_investigation' => abs($variance) > 100, // Threshold for investigation
            ],
        ];
    }

    /**
     * Get audit details by transaction type.
     *
     * @param FinancialAuditLog $audit
     * @param string $type
     * @return array
     */
    public function getTransactionDetails(FinancialAuditLog $audit, string $type): array
    {
        $startDate = $audit->start_date;
        $endDate = $audit->end_date;
        $businessId = $audit->business_id;

        switch ($type) {
            case 'sales':
                return Sale::where('business_id', $businessId)
                    ->whereBetween('saleDate', [$startDate, $endDate])
                    ->with(['party:id,name'])
                    ->get()
                    ->map(function ($sale) {
                        return [
                            'id' => $sale->id,
                            'invoice_number' => $sale->invoiceNumber,
                            'date' => $sale->saleDate->format('Y-m-d'),
                            'amount' => $sale->totalAmount,
                            'customer' => $sale->party->name ?? 'Walk-in',
                            'payment_status' => $sale->isPaid ? 'Paid' : 'Unpaid',
                        ];
                    })->toArray();

            case 'purchases':
                return Purchase::where('business_id', $businessId)
                    ->whereBetween('purchaseDate', [$startDate, $endDate])
                    ->with(['party:id,name'])
                    ->get()
                    ->map(function ($purchase) {
                        return [
                            'id' => $purchase->id,
                            'invoice_number' => $purchase->invoiceNumber,
                            'date' => $purchase->purchaseDate->format('Y-m-d'),
                            'amount' => $purchase->totalAmount,
                            'supplier' => $purchase->party->name ?? 'Unknown',
                            'payment_status' => $purchase->isPaid ? 'Paid' : 'Unpaid',
                        ];
                    })->toArray();

            case 'income':
                return Income::where('business_id', $businessId)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->with(['incomeCategory:id,name'])
                    ->get()
                    ->map(function ($income) {
                        return [
                            'id' => $income->id,
                            'date' => $income->date->format('Y-m-d'),
                            'amount' => $income->amount,
                            'category' => $income->incomeCategory->name ?? 'Uncategorized',
                            'description' => $income->description,
                        ];
                    })->toArray();

            case 'expenses':
                return Expense::where('business_id', $businessId)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->with(['expenseCategory:id,name'])
                    ->get()
                    ->map(function ($expense) {
                        return [
                            'id' => $expense->id,
                            'date' => $expense->date->format('Y-m-d'),
                            'amount' => $expense->amount,
                            'category' => $expense->expenseCategory->name ?? 'Uncategorized',
                            'description' => $expense->description,
                        ];
                    })->toArray();

            default:
                return [];
        }
    }

    /**
     * Get comparative audit report (compare multiple periods).
     *
     * @param int $businessId
     * @param array $periods
     * @return array
     */
    public function getComparativeReport(int $businessId, array $periods): array
    {
        $reports = [];

        foreach ($periods as $period) {
            $audit = FinancialAuditLog::where('business_id', $businessId)
                ->where('start_date', $period['start_date'])
                ->where('end_date', $period['end_date'])
                ->where('status', 'completed')
                ->first();

            if ($audit) {
                $reports[] = [
                    'period' => $period,
                    'report' => $this->getAuditReport($audit),
                ];
            }
        }

        return $reports;
    }
}