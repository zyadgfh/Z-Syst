<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Party;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Business;
use App\Models\PurchaseDetails;
use App\Models\AutoOrderRule;
use App\Models\AutoOrderSuggestion;
use App\Models\SalesForecast;
use App\Models\PredictionSetting;
use App\Helpers\TransactionHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoOrderService
{
    protected PredictionService $predictionService;

    public function __construct(PredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    /**
     * Generate auto-order suggestions for all products that need reordering.
     *
     * @param int $businessId
     * @return array
     */
    public function generateSuggestions(int $businessId): array
    {
        $settings = PredictionSetting::getForBusiness($businessId);
        if (!$settings->prediction_enabled) {
            return [
                'success' => false,
                'message' => 'التنبؤات معطلة. قم بتمكينها أولاً.',
                'suggestions' => [],
            ];
        }

        $rules = AutoOrderRule::where('business_id', $businessId)
            ->where('enabled', true)
            ->with('product')
            ->get();

        if ($rules->isEmpty()) {
            // Create default rules for all products
            $this->createDefaultRules($businessId);
            $rules = AutoOrderRule::where('business_id', $businessId)
                ->where('enabled', true)
                ->with('product')
                ->get();
        }

        $suggestions = [];
        $generatedCount = 0;

        foreach ($rules as $rule) {
            try {
                $suggestion = $this->evaluateProduct($rule, $businessId);
                if ($suggestion && $suggestion['suggested_order_qty'] > 0) {
                    $suggestions[] = $suggestion;
                    $generatedCount++;
                }
            } catch (\Exception $e) {
                Log::error("AutoOrder error for product {$rule->product_id}: {$e->getMessage()}");
            }
        }

        // Sort by priority (high first)
        usort($suggestions, fn($a, $b) => 
            $this->priorityWeight($b['priority']) <=> $this->priorityWeight($a['priority'])
        );

        return [
            'success' => true,
            'business_id' => $businessId,
            'total_evaluated' => $rules->count(),
            'suggestions_count' => $generatedCount,
            'suggestions' => $suggestions,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Evaluate a single product and create suggestion if needed.
     */
    private function evaluateProduct(AutoOrderRule $rule, int $businessId): ?array
    {
        $product = $rule->product;
        $settings = PredictionSetting::getForBusiness($businessId);

        // Get current stock
        $currentStock = (float) Stock::where('product_id', $product->id)
            ->where('business_id', $businessId)
            ->sum('productStock');

        // Get pending purchases (not yet received)
        $pendingPurchases = (float) PurchaseDetails::whereHas('purchase', function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->where('product_id', $product->id)
            ->whereNull('expire_date') // not yet fully processed
            ->sum('quantities');

        // Get forecasted demand
        $forecastDays = $settings->forecast_days;
        $forecasts = SalesForecast::where('business_id', $businessId)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->where('forecast_date', '>=', now()->startOfDay())
            ->where('forecast_date', '<=', now()->startOfDay()->addDays($forecastDays))
            ->get();

        $predictedDemand = $forecasts->sum('predicted_quantity');
        $avgConfidence = $forecasts->avg('confidence_score');

        // If no forecasts, generate them
        if ($forecasts->isEmpty()) {
            try {
                $forecastResult = $this->predictionService->forecastProduct($product->id, $businessId);
                $predictedDemand = $forecastResult['summary']['total_predicted_qty'];
                $avgConfidence = $forecastResult['summary']['daily_average'] > 0 ? 70 : 0;
            } catch (\Exception $e) {
                Log::warning("Cannot forecast product {$product->id}: {$e->getMessage()}");
                // Fall back to simple calculation using average from sales
                $predictedDemand = $this->estimateDemand($product->id, $businessId);
                $avgConfidence = 50;
            }
        }

        // Calculate reorder point
        $leadTime = $rule->lead_time_days ?? $settings->lead_time_days;
        $dailyDemand = $forecastDays > 0 ? ($predictedDemand / $forecastDays) : 0;
        $leadTimeDemand = $dailyDemand * $leadTime;
        $safetyStock = $leadTimeDemand * ($settings->safety_stock_multiplier - 1);
        $reorderPoint = $leadTimeDemand + $safetyStock;

        // Check if reorder is needed
        $availableStock = $currentStock + $pendingPurchases;
        $needsReorder = $availableStock <= $reorderPoint;

        if (!$needsReorder && $rule->reorder_point) {
            $needsReorder = $availableStock <= $rule->reorder_point;
        }

        if (!$needsReorder) {
            return null;
        }

        // Calculate suggested order quantity
        $maxStock = $rule->max_stock_level ?? ($reorderPoint * 3);
        $suggestedQty = max(0, $maxStock - $availableStock);

        // Apply min/max constraints
        if ($rule->min_order_qty && $suggestedQty < $rule->min_order_qty) {
            $suggestedQty = $rule->min_order_qty;
        }
        if ($rule->max_order_qty && $suggestedQty > $rule->max_order_qty) {
            $suggestedQty = $rule->max_order_qty;
        }

        // Apply order multiple (e.g., box of 12)
        if ($rule->order_multiple && $rule->order_multiple > 0) {
            $suggestedQty = ceil($suggestedQty / $rule->order_multiple) * $rule->order_multiple;
        }

        // Ensure at least minimum
        if ($suggestedQty < 1) {
            return null;
        }

        // Determine priority
        $stockRatio = $currentStock / max($predictedDemand, 1);
        $priority = $this->determinePriority($stockRatio, $avgConfidence);

        // Create or update suggestion record
        $existingSuggestion = AutoOrderSuggestion::where('business_id', $businessId)
            ->where('product_id', $product->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingSuggestion) {
            $existingSuggestion->update([
                'predicted_demand' => $predictedDemand,
                'current_stock' => $currentStock,
                'pending_purchases' => $pendingPurchases,
                'suggested_order_qty' => $suggestedQty,
                'confidence_score' => $avgConfidence,
                'priority' => $priority,
                'reasoning' => [
                    'reorder_point' => $reorderPoint,
                    'available_stock' => $availableStock,
                    'lead_time_demand' => $leadTimeDemand,
                    'safety_stock' => $safetyStock,
                    'daily_demand' => $dailyDemand,
                    'lead_time_days' => $leadTime,
                    'stock_coverage_days' => $dailyDemand > 0 ? round($currentStock / $dailyDemand, 1) : 'N/A',
                ],
            ]);
            $suggestionId = $existingSuggestion->id;
        } else {
            $newSuggestion = AutoOrderSuggestion::create([
                'business_id' => $businessId,
                'product_id' => $product->id,
                'preferred_supplier_id' => $rule->preferred_supplier_id,
                'predicted_demand' => $predictedDemand,
                'current_stock' => $currentStock,
                'pending_purchases' => $pendingPurchases,
                'suggested_order_qty' => $suggestedQty,
                'confidence_score' => $avgConfidence,
                'priority' => $priority,
                'status' => $rule->auto_approve ? 'approved' : 'pending',
                'notes' => $rule->auto_approve ? 'تمت الموافقة تلقائياً' : null,
                'reasoning' => [
                    'reorder_point' => $reorderPoint,
                    'available_stock' => $availableStock,
                    'lead_time_demand' => $leadTimeDemand,
                    'safety_stock' => $safetyStock,
                    'daily_demand' => $dailyDemand,
                    'lead_time_days' => $leadTime,
                    'stock_coverage_days' => $dailyDemand > 0 ? round($currentStock / $dailyDemand, 1) : 'N/A',
                ],
            ]);
            $suggestionId = $newSuggestion->id;
        }

        return [
            'id' => $suggestionId,
            'product_id' => $product->id,
            'product_name' => $product->productName,
            'product_code' => $product->productCode,
            'current_stock' => (float) $currentStock,
            'pending_purchases' => (float) $pendingPurchases,
            'predicted_demand' => round($predictedDemand, 2),
            'suggested_order_qty' => (float) $suggestedQty,
            'confidence_score' => $avgConfidence ? round($avgConfidence, 1) : null,
            'priority' => $priority,
            'preferred_supplier_id' => $rule->preferred_supplier_id,
            'reorder_point' => round($reorderPoint, 2),
            'sales_price' => $product->sales_price,
            'purchase_price' => $product->purchase_with_tax,
            'status' => $rule->auto_approve ? 'approved' : 'pending',
        ];
    }

    /**
     * Approve a suggestion.
     */
    public function approveSuggestion(int $suggestionId, int $userId): array
    {
        $suggestion = AutoOrderSuggestion::findOrFail($suggestionId);
        
        $suggestion->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'تمت الموافقة على الاقتراح.',
            'suggestion' => $suggestion->load('product:id,productName'),
        ];
    }

    /**
     * Reject a suggestion.
     */
    public function rejectSuggestion(int $suggestionId, ?string $reason = null): array
    {
        $suggestion = AutoOrderSuggestion::findOrFail($suggestionId);

        $suggestion->update([
            'status' => 'rejected',
            'notes' => $reason,
        ]);

        return [
            'success' => true,
            'message' => 'تم رفض الاقتراح.',
        ];
    }

    /**
     * Convert an approved suggestion into a purchase order.
     *
     * @param int $suggestionId
     * @param array $purchaseData Additional purchase data
     * @return array
     */
    public function convertToPurchase(int $suggestionId, array $purchaseData = []): array
    {
        $suggestion = AutoOrderSuggestion::with('product')->findOrFail($suggestionId);

        if ($suggestion->status !== 'approved') {
            return [
                'success' => false,
                'message' => 'يجب الموافقة على الاقتراح أولاً.',
            ];
        }

        if ($suggestion->converted_purchase_id) {
            return [
                'success' => false,
                'message' => 'تم تحويل هذا الاقتراح بالفعل إلى فاتورة شراء.',
            ];
        }

        $businessId = $suggestion->business_id;
        $product = $suggestion->product;
        $supplierId = $suggestion->preferred_supplier_id ?? $purchaseData['party_id'] ?? null;

        if (!$supplierId) {
            // Find a party that supplies this product
            $supplier = Party::where('business_id', $businessId)
                ->where('type', 'supplier')
                ->first();

            if (!$supplier) {
                return [
                    'success' => false,
                    'message' => 'لا يوجد مورد متاح. يرجى إضافة مورد أولاً.',
                ];
            }
            $supplierId = $supplier->id;
        }

        $totalAmount = $suggestion->suggested_order_qty * ($product->purchase_with_tax ?? 0);

        // Use TransactionHelper to create purchase
        $purchase = TransactionHelper::run(function () use ($suggestion, $product, $supplierId, $businessId, $totalAmount) {
            $purchase = Purchase::create([
                'party_id' => $supplierId,
                'business_id' => $businessId,
                'user_id' => auth()->id(),
                'purchaseDate' => now()->format('Y-m-d'),
                'totalAmount' => $totalAmount,
                'paidAmount' => 0,
                'dueAmount' => $totalAmount,
                'isPaid' => false,
                'paymentType' => 'credit',
                'note' => 'طلب تلقائي من نظام التنبؤ - ' . ($product->productName ?? ''),
            ]);

            // Create purchase detail
            PurchaseDetails::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product->id,
                'purchase_without_tax' => $product->purchase_without_tax ?? 0,
                'purchase_with_tax' => $product->purchase_with_tax ?? 0,
                'profit_percent' => $product->profit_percent ?? 0,
                'sales_price' => $product->sales_price ?? 0,
                'wholesale_price' => $product->wholesale_price ?? 0,
                'quantities' => $suggestion->suggested_order_qty,
                'batch_no' => 'AUTO-' . strtoupper(uniqid()),
                'expire_date' => now()->addYears(2)->format('Y-m-d'), // default 2 years
            ]);

            // Update stock
            $stock = Stock::where('product_id', $product->id)
                ->where('batch_no', 'LIKE', 'AUTO-%')
                ->first();

            if ($stock) {
                $stock->increment('productStock', $suggestion->suggested_order_qty);
            } else {
                Stock::create([
                    'business_id' => $businessId,
                    'product_id' => $product->id,
                    'batch_no' => 'AUTO-' . strtoupper(uniqid()),
                    'expire_date' => now()->addYears(2)->format('Y-m-d'),
                    'productStock' => $suggestion->suggested_order_qty,
                ]);
            }

            return $purchase;
        }, 'auto_order:convert', [
            'suggestion_id' => $suggestion->id,
            'product_id' => $product->id,
        ]);

        // Update suggestion
        $suggestion->update([
            'status' => 'converted',
            'converted_purchase_id' => $purchase->id,
            'converted_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'تم تحويل الاقتراح إلى فاتورة شراء بنجاح.',
            'purchase_id' => $purchase->id,
            'invoice_number' => $purchase->invoiceNumber,
            'purchase' => $purchase->load('details.product:id,productName'),
        ];
    }

    /**
     * Get all suggestions with filtering.
     */
    public function getSuggestions(int $businessId, array $filters = []): array
    {
        $query = AutoOrderSuggestion::where('business_id', $businessId)
            ->with([
                'product:id,productName,productCode,sales_price',
                'preferredSupplier:id,name,phone',
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('productName', 'like', "%{$filters['search']}%")
                  ->orWhere('productCode', 'like', "%{$filters['search']}%");
            });
        }

        $perPage = $filters['per_page'] ?? 20;
        $suggestions = $query->orderBy('priority', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Add summary stats
        $stats = [
            'total_pending' => AutoOrderSuggestion::where('business_id', $businessId)->where('status', 'pending')->count(),
            'total_approved' => AutoOrderSuggestion::where('business_id', $businessId)->where('status', 'approved')->count(),
            'total_converted' => AutoOrderSuggestion::where('business_id', $businessId)->where('status', 'converted')->count(),
            'total_high_priority' => AutoOrderSuggestion::where('business_id', $businessId)->where('priority', 'high')->whereIn('status', ['pending', 'approved'])->count(),
        ];

        return [
            'suggestions' => $suggestions,
            'stats' => $stats,
        ];
    }

    /**
     * Get auto-order report.
     */
    public function getReport(int $businessId): array
    {
        $suggestions = AutoOrderSuggestion::where('business_id', $businessId)
            ->with('product:id,productName')
            ->get();

        $totalSuggestedQty = $suggestions->sum('suggested_order_qty');
        $totalPotentialValue = $suggestions->sum(function ($s) {
            return $s->suggested_order_qty * ($s->product->purchase_with_tax ?? 0);
        });

        // Group by status
        $byStatus = $suggestions->groupBy('status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_qty' => $group->sum('suggested_order_qty'),
            ];
        });

        return [
            'business_id' => $businessId,
            'total_suggestions' => $suggestions->count(),
            'total_suggested_qty' => round($totalSuggestedQty, 2),
            'total_potential_value' => round($totalPotentialValue, 2),
            'by_status' => $byStatus,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Create default auto-order rules for all products.
     */
    private function createDefaultRules(int $businessId): void
    {
        $products = Product::where('business_id', $businessId)->get();

        foreach ($products as $product) {
            AutoOrderRule::firstOrCreate(
                [
                    'business_id' => $businessId,
                    'product_id' => $product->id,
                ],
                [
                    'enabled' => true,
                    'lead_time_days' => 7,
                    'min_order_qty' => 1,
                    'order_multiple' => 1,
                    'auto_approve' => false,
                ]
            );
        }
    }

    /**
     * Estimate demand from sales when forecast data isn't available.
     */
    private function estimateDemand(int $productId, int $businessId): float
    {
        $salesData = DB::table('sale_details')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->where('sale_details.product_id', $productId)
            ->where('sales.business_id', $businessId)
            ->where('sales.saleDate', '>=', now()->subDays(90))
            ->sum('sale_details.quantities');

        return $salesData / 90 * 30; // Project for 30 days
    }

    /**
     * Determine priority weight for sorting.
     */
    private function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    /**
     * Determine priority based on stock ratio and confidence.
     */
    private function determinePriority(float $stockRatio, ?float $confidence): string
    {
        if ($stockRatio <= 0.1) return 'high';     // Critical (less than 10% of forecast)
        if ($stockRatio <= 0.3) return 'high';     // Very low
        if ($stockRatio <= 0.5) return 'medium';   // Low
        if ($stockRatio <= 0.7) return 'medium';   // Below average
        return 'low';                                // Sufficient
    }
}

