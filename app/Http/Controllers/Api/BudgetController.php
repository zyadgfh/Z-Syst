<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudgetAlert;
use App\Models\PurchaseBudget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * BudgetController — purchase budgets with live usage (C5).
 *
 * index returns each budget with computed usage_percent, remaining and any
 * unresolved alerts. The legacy schema carries spent/remaining columns, so
 * usage is derived from recorded spend and exposed alongside the raw rows.
 */
class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $businessId = auth()->user()->business_id;

        $budgets = PurchaseBudget::where('business_id', $businessId)
            ->with('category:id,name')
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('start_date')
            ->get()
            ->map(function (PurchaseBudget $budget) {
                $budgetAmount = (float) $budget->budget_amount;
                $spent = (float) $budget->spent_amount;

                $budget->usage_percent = $budgetAmount > 0 ? round($spent / $budgetAmount * 100, 1) : 0.0;
                $budget->remaining_amount = round($budgetAmount - $spent, 2);
                $budget->health = match (true) {
                    $budget->usage_percent >= 100 => 'exceeded',
                    $budget->usage_percent >= (float) ($budget->alerts()->whereNull('resolved_at')->latest('id')->value('threshold') ?? 80) => 'warning',
                    default => 'ok',
                };

                return $budget;
            });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $budgets,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $businessId = auth()->user()->business_id;

        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'period' => 'required|string|max:20', // e.g. 2026-11 / 2026-Q4 / 2026
            'budget_amount' => 'required|numeric|min:0.01',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|integer',
        ]);

        // Path B: created_by/approved_by are legacy integer FKs; uuid actors stay null.
        $actorId = auth()->id();
        $budget = PurchaseBudget::create($validated + [
            'business_id' => $businessId,
            'spent_amount' => 0,
            'remaining_amount' => $validated['budget_amount'],
            'status' => 'active',
            'created_by' => is_int($actorId) ? $actorId : (ctype_digit((string) $actorId) ? (int) $actorId : null),
        ]);

        return response()->json([
            'message' => __('Budget created successfully.'),
            'data' => $budget,
        ], 201);
    }

    /**
     * Record a spend against a budget (keeps spent/remaining coherent) and
     * auto-raise the 80%/100% alerts the first time a threshold is crossed.
     */
    public function recordSpend(Request $request, PurchaseBudget $budget): JsonResponse
    {
        abort_unless($budget->business_id === auth()->user()->business_id, 404);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $raised = DB::transaction(function () use ($budget, $validated) {
            $budget->increment('spent_amount', $validated['amount']);
            $budget->decrement('remaining_amount', $validated['amount']);

            $usage = $budget->budget_amount > 0
                ? $budget->spent_amount / $budget->budget_amount * 100
                : 0;

            $raised = [];
            foreach ([[80, 'threshold_80'], [100, 'exceeded']] as [$threshold, $type]) {
                if ($usage >= $threshold && ! $budget->alerts()->where('alert_type', $type)->exists()) {
                    BudgetAlert::create([
                        'budget_id' => $budget->id,
                        'business_id' => $budget->business_id,
                        'alert_type' => $type,
                        'threshold' => $threshold,
                        'notes' => 'Usage reached ' . round($usage, 1) . '%',
                    ]);
                    $raised[] = $type;
                }
            }

            return $raised;
        });

        return response()->json([
            'message' => __('Spend recorded successfully.'),
            'data' => [
                'budget' => $budget->fresh(),
                'usage_percent' => $budget->budget_amount > 0 ? round($budget->spent_amount / $budget->budget_amount * 100, 1) : 0,
                'alerts_raised' => $raised,
            ],
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $businessId = auth()->user()->business_id;

        $alerts = BudgetAlert::whereHas('budget', fn ($q) => $q->where('business_id', $businessId))
            ->with('budget:id,period,category_id,budget_amount,spent_amount')
            ->when($request->boolean('unresolved'), fn ($q) => $q->whereNull('resolved_at'))
            ->latest('id')
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $alerts,
        ]);
    }
}
