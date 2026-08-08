<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutoOrderRule;
use App\Models\AutoOrderSuggestion;
use App\Services\AutoOrderService;
use Illuminate\Http\Request;

class AutoOrderController extends Controller
{
    protected AutoOrderService $autoOrderService;

    public function __construct(AutoOrderService $autoOrderService)
    {
        $this->autoOrderService = $autoOrderService;
    }

    /**
     * Get auto-order settings (rules) for all products.
     */
    public function settings(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $rules = AutoOrderRule::where('business_id', $businessId)
            ->with('product:id,productName,productCode', 'preferredSupplier:id,name')
            ->paginate($request->input('per_page', 20));

        $stats = [
            'total_rules' => AutoOrderRule::where('business_id', $businessId)->count(),
            'enabled_rules' => AutoOrderRule::where('business_id', $businessId)->where('enabled', true)->count(),
            'auto_approve_rules' => AutoOrderRule::where('business_id', $businessId)->where('auto_approve', true)->count(),
        ];

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $rules,
            'stats' => $stats,
        ]);
    }

    /**
     * Update auto-order rule for a specific product.
     */
    public function updateRule(Request $request, $productId)
    {
        $businessId = auth()->user()->business_id;

        $request->validate([
            'enabled' => 'boolean',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:1|max:90',
            'min_order_qty' => 'nullable|numeric|min:0',
            'max_order_qty' => 'nullable|numeric|min:0',
            'order_multiple' => 'nullable|numeric|min:0',
            'preferred_supplier_id' => 'nullable|exists:parties,id',
            'auto_approve' => 'boolean',
        ]);

        $rule = AutoOrderRule::updateOrCreate(
            [
                'business_id' => $businessId,
                'product_id' => $productId,
            ],
            $request->only([
                'enabled',
                'min_stock_level',
                'max_stock_level',
                'reorder_point',
                'lead_time_days',
                'min_order_qty',
                'max_order_qty',
                'order_multiple',
                'preferred_supplier_id',
                'auto_approve',
            ])
        );

        return response()->json([
            'message' => __('Rule updated successfully.'),
            'data' => $rule->load('product:id,productName', 'preferredSupplier:id,name'),
        ]);
    }

    /**
     * Get auto-order rule for a specific product.
     */
    public function getRule($productId)
    {
        $businessId = auth()->user()->business_id;

        $rule = AutoOrderRule::where('business_id', $businessId)
            ->where('product_id', $productId)
            ->with('product:id,productName,productCode,sales_price', 'preferredSupplier:id,name,phone')
            ->first();

        if (! $rule) {
            return response()->json([
                'message' => __('No rule found for this product.'),
                'data' => null,
            ]);
        }

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $rule,
        ]);
    }

    /**
     * Generate auto-order suggestions.
     */
    public function generateSuggestions()
    {
        $businessId = auth()->user()->business_id;

        $result = $this->autoOrderService->generateSuggestions($businessId);

        return response()->json([
            'message' => $result['success']
                ? __('Suggestions generated successfully. :count suggestions created', ['count' => $result['suggestions_count']])
                : $result['message'],
            'data' => $result,
        ]);
    }

    /**
     * Get all suggestions with filters.
     */
    public function getSuggestions(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $filters = $request->only(['status', 'priority', 'search', 'per_page']);
        $result = $this->autoOrderService->getSuggestions($businessId, $filters);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $result['suggestions'],
            'stats' => $result['stats'],
        ]);
    }

    /**
     * Approve a suggestion.
     */
    public function approveSuggestion($id)
    {
        $businessId = auth()->user()->business_id;

        $suggestion = AutoOrderSuggestion::where('business_id', $businessId)
            ->findOrFail($id);

        $result = $this->autoOrderService->approveSuggestion($id, auth()->id());

        return response()->json([
            'message' => $result['message'],
            'data' => $result['suggestion'],
        ]);
    }

    /**
     * Reject a suggestion.
     */
    public function rejectSuggestion(Request $request, $id)
    {
        $businessId = auth()->user()->business_id;

        $suggestion = AutoOrderSuggestion::where('business_id', $businessId)
            ->findOrFail($id);

        $result = $this->autoOrderService->rejectSuggestion($id, $request->reason);

        return response()->json([
            'message' => $result['message'],
        ]);
    }

    /**
     * Confirm/converts suggestion to purchase.
     */
    public function confirmSuggestion(Request $request, $id)
    {
        $businessId = auth()->user()->business_id;

        $suggestion = AutoOrderSuggestion::where('business_id', $businessId)
            ->findOrFail($id);

        $result = $this->autoOrderService->convertToPurchase($id, $request->all());

        return response()->json([
            'message' => $result['message'],
            'data' => $result,
        ]);
    }

    /**
     * Get auto-order report.
     */
    public function report()
    {
        $businessId = auth()->user()->business_id;

        $result = $this->autoOrderService->getReport($businessId);

        return response()->json([
            'message' => __('Report fetched successfully.'),
            'data' => $result,
        ]);
    }

    /**
     * Bulk update rules.
     */
    public function bulkUpdateRules(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $request->validate([
            'rules' => 'required|array',
            'rules.*.product_id' => 'required|exists:products,id',
            'rules.*.enabled' => 'boolean',
            'rules.*.auto_approve' => 'boolean',
        ]);

        $updated = 0;
        foreach ($request->rules as $ruleData) {
            AutoOrderRule::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'product_id' => $ruleData['product_id'],
                ],
                collect($ruleData)->except('product_id')->toArray()
            );
            $updated++;
        }

        return response()->json([
            'message' => __(':count rules updated successfully.', ['count' => $updated]),
        ]);
    }
}
