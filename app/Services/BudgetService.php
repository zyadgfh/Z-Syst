<?php

namespace App\Services;

use App\Models\PurchaseBudget;
use App\Models\BudgetAlert;
use App\Models\BudgetTransaction;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function create(array $data): PurchaseBudget
    {
        return DB::transaction(function () use ($data) {
            $data['remaining_amount'] = $data['budget_amount'];
            return PurchaseBudget::create($data);
        });
    }

    public function recordTransaction(PurchaseBudget $budget, array $data): BudgetTransaction
    {
        return DB::transaction(function () use ($budget, $data) {
            $transaction = BudgetTransaction::create([
                'budget_id' => $budget->id,
                'purchase_id' => $data['purchase_id'] ?? null,
                'amount' => $data['amount'],
                'transaction_date' => $data['transaction_date'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $budget->increment('spent_amount', $data['amount']);
            $budget->decrement('remaining_amount', $data['amount']);

            $this->checkBudgetAlerts($budget);

            return $transaction;
        });
    }

    protected function checkBudgetAlerts(PurchaseBudget $budget): void
    {
        $spentPercentage = $budget->spent_percentage;

        if ($spentPercentage >= 90 && $spentPercentage < 100) {
            BudgetAlert::firstOrCreate([
                'budget_id' => $budget->id,
                'business_id' => $budget->business_id,
                'alert_type' => 'warning',
                'threshold' => 90,
            ]);
        } elseif ($spentPercentage >= 100) {
            BudgetAlert::firstOrCreate([
                'budget_id' => $budget->id,
                'business_id' => $budget->business_id,
                'alert_type' => 'critical',
                'threshold' => 100,
            ]);
        }
    }

    public function getActiveBudgets(int $businessId)
    {
        return PurchaseBudget::forBusiness($businessId)
            ->active()
            ->with(['alerts', 'transactions'])
            ->latest()
            ->get();
    }

    public function getBudgetAlerts(int $businessId)
    {
        return BudgetAlert::whereHas('budget', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
        })->unresolved()
        ->with(['budget'])
        ->latest()
        ->get();
    }
}
