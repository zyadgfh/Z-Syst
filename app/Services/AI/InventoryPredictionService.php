<?php

namespace App\Services\AI;

use App\Models\Product;
use App\Models\Stock;
use App\Models\SaleDetails;
use App\Models\PurchaseDetails;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryPredictionService
{
    use WithTransactionalOperations;

    /**
     * Predict demand for a product using historical sales data.
     *
     * @param int $productId
     * @param int $businessId
     * @param int $daysToPredict
     * @return array
     */
    public function predictDemand(int $productId, int $businessId, int $daysToPredict = 30): array
    {
        $product = Product::findOrFail($productId);
        
        // Get historical sales data for the last 90 days
        $historicalSales = SaleDetails::where('product_id', $productId)
            ->whereHas('sale', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->where('created_at', '>=', now()->subDays(90))
            ->selectRaw('DATE(created_at) as date, SUM(quantities) as total_quantity')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Calculate daily average and trend
        $dailySales = $this->calculateDailySalesPattern($historicalSales);
        $weeklyTrend = $this->calculateWeeklyTrend($historicalSales);
        $seasonalFactor = $this->calculateSeasonalFactor($historicalSales);

        // Generate predictions
        $predictions = [];
        $currentDate = now();
        $baseDemand = $dailySales['average_daily'] ?? 0;

        for ($i = 1; $i <= $daysToPredict; $i++) {
            $predictionDate = $currentDate->copy()->addDays($i);
            
            // Apply seasonal adjustment
            $dayOfWeek = $predictionDate->dayOfWeek;
            $seasonalMultiplier = $seasonalFactor[$dayOfWeek] ?? 1.0;
            
            // Apply trend
            $trendMultiplier = 1 + ($weeklyTrend * 0.01); // 1% per week trend
            
            // Calculate predicted demand
            $predictedDemand = round($baseDemand * $seasonalMultiplier * $trendMultiplier);
            
            // Add confidence interval
            $confidence = $this->calculateConfidence($historicalSales, $predictedDemand);
            
            $predictions[] = [
                'date' => $predictionDate->toDateString(),
                'predicted_demand' => max(0, $predictedDemand),
                'confidence_low' => max(0, round($predictedDemand * (1 - $confidence))),
                'confidence_high' => round($predictedDemand * (1 + $confidence)),
                'confidence_level' => $confidence,
            ];
        }

        return [
            'product_id' => $productId,
            'product_name' => $product->productName,
            'prediction_period_days' => $daysToPredict,
            'total_predicted_demand' => array_sum(array_column($predictions, 'predicted_demand')),
            'average_daily_demand' => $dailySales['average_daily'] ?? 0,
            'weekly_trend' => $weeklyTrend,
            'predictions' => $predictions,
            'current_stock' => $this->getCurrentStock($productId, $businessId),
            'recommended_reorder' => $this->calculateReorderPoint($productId, $businessId, $predictions),
        ];
    }

    /**
     * Calculate daily sales pattern.
     *
     * @param Collection $historicalSales
     * @return array
     */
    protected function calculateDailySalesPattern(Collection $historicalSales): array
    {
        if ($historicalSales->isEmpty()) {
            return ['average_daily' => 0, 'pattern' => []];
        }

        $totalQuantity = $historicalSales->sum('total_quantity');
        $daysCount = $historicalSales->count();
        
        $pattern = [];
        foreach ($historicalSales as $sale) {
            $dayOfWeek = Carbon::parse($sale->date)->dayOfWeek;
            $pattern[$dayOfWeek] = ($pattern[$dayOfWeek] ?? 0) + $sale->total_quantity;
        }

        // Normalize pattern
        foreach ($pattern as $day => $quantity) {
            $pattern[$day] = $quantity / ($daysCount / 7); // Average per occurrence
        }

        return [
            'average_daily' => $totalQuantity / max($daysCount, 1),
            'pattern' => $pattern,
        ];
    }

    /**
     * Calculate weekly trend.
     *
     * @param Collection $historicalSales
     * @return float
     */
    protected function calculateWeeklyTrend(Collection $historicalSales): float
    {
        if ($historicalSales->count() < 14) {
            return 0; // Not enough data for trend
        }

        $salesByWeek = $historicalSales->groupBy(function ($sale) {
            return Carbon::parse($sale->date)->weekOfYear;
        });

        $weeklyTotals = $salesByWeek->map->sum('total_quantity')->values();
        
        if ($weeklyTotals->count() < 2) {
            return 0;
        }

        // Simple linear regression slope
        $n = $weeklyTotals->count();
        $x = range(1, $n);
        $y = $weeklyTotals->toArray();
        
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(function ($xi, $yi) { return $xi * $yi; }, $x, $y));
        $sumX2 = array_sum(array_map(function ($xi) { return $xi * $xi; }, $x));
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        return $slope; // Positive = increasing trend, Negative = decreasing
    }

    /**
     * Calculate seasonal factor.
     *
     * @param Collection $historicalSales
     * @return array
     */
    protected function calculateSeasonalFactor(Collection $historicalSales): array
    {
        $dayOfWeekSales = array_fill(0, 7, 0);
        $dayOfWeekCounts = array_fill(0, 7, 0);

        foreach ($historicalSales as $sale) {
            $dayOfWeek = Carbon::parse($sale->date)->dayOfWeek;
            $dayOfWeekSales[$dayOfWeek] += $sale->total_quantity;
            $dayOfWeekCounts[$dayOfWeek]++;
        }

        $averageDaily = array_sum($dayOfWeekSales) / max(array_sum($dayOfWeekCounts), 1);
        
        $seasonalFactor = [];
        for ($i = 0; $i < 7; $i++) {
            $seasonalFactor[$i] = $dayOfWeekCounts[$i] > 0 
                ? ($dayOfWeekSales[$i] / $dayOfWeekCounts[$i]) / $averageDaily
                : 1.0;
        }

        return $seasonalFactor;
    }

    /**
     * Calculate confidence interval.
     *
     * @param Collection $historicalSales
     * @param float $predictedDemand
     * @return float
     */
    protected function calculateConfidence(Collection $historicalSales, float $predictedDemand): float
    {
        if ($historicalSales->count() < 5) {
            return 0.5; // Low confidence with limited data
        }

        $quantities = $historicalSales->pluck('total_quantity')->toArray();
        $stdDev = $this->calculateStandardDeviation($quantities);
        
        if ($stdDev == 0) {
            return 0.1; // Very confident if no variance
        }

        $coefficientOfVariation = $stdDev / max($predictedDemand, 1);
        
        // More variance = less confidence
        return min(max(0.1, 1 - $coefficientOfVariation), 0.5);
    }

    /**
     * Calculate standard deviation.
     *
     * @param array $values
     * @return float
     */
    protected function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) return 0;

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values)) / $count;

        return sqrt($variance);
    }

    /**
     * Get current stock for a product.
     *
     * @param int $productId
     * @param int $businessId
     * @return int
     */
    protected function getCurrentStock(int $productId, int $businessId): int
    {
        return Stock::where('business_id', $businessId)
            ->where('product_id', $productId)
            ->sum('productStock');
    }

    /**
     * Calculate reorder point.
     *
     * @param int $productId
     * @param int $businessId
     * @param array $predictions
     * @return array
     */
    protected function calculateReorderPoint(int $productId, int $businessId, array $predictions): array
    {
        $currentStock = $this->getCurrentStock($productId, $businessId);
        $leadTimeDays = 7; // Default lead time - should be configurable
        $safetyStockDays = 3; // Safety stock - should be configurable
        
        // Calculate demand during lead time
        $leadTimeDemand = array_sum(array_slice(
            array_column($predictions, 'predicted_demand'),
            0,
            $leadTimeDays
        ));
        
        // Calculate safety stock
        $safetyStock = array_sum(array_slice(
            array_column($predictions, 'predicted_demand'),
            0,
            $safetyStockDays
        ));
        
        $reorderPoint = $leadTimeDemand + $safetyStock;
        
        return [
            'current_stock' => $currentStock,
            'reorder_point' => $reorderPoint,
            'should_reorder' => $currentStock <= $reorderPoint,
            'recommended_order_quantity' => max(0, $reorderPoint - $currentStock + $leadTimeDemand),
            'lead_time_days' => $leadTimeDays,
            'safety_stock' => $safetyStock,
        ];
    }

    /**
     * Generate bulk predictions for multiple products.
     *
     * @param array<int> $productIds
     * @param int $businessId
     * @param int $daysToPredict
     * @return Collection
     */
    public function generateBulkPredictions(array $productIds, int $businessId, int $daysToPredict = 30): Collection
    {
        $predictions = collect();

        foreach ($productIds as $productId) {
            try {
                $prediction = $this->predictDemand($productId, $businessId, $daysToPredict);
                $predictions->push($prediction);
            } catch (\Exception $e) {
                // Skip products with insufficient data
                continue;
            }
        }

        return $predictions;
    }

    /**
     * Get products that need reordering.
     *
     * @param int $businessId
     * @return Collection
     */
    public function getProductsNeedingReorder(int $businessId): Collection
    {
        $products = Product::where('business_id', $businessId)
            ->where('alert_qty', '>', 0)
            ->get();

        $productsNeedingReorder = collect();

        foreach ($products as $product) {
            $currentStock = $this->getCurrentStock($product->id, $businessId);
            
            if ($currentStock <= $product->alert_qty) {
                $predictions = $this->predictDemand($product->id, $businessId, 30);
                $productsNeedingReorder->push([
                    'product' => $product,
                    'current_stock' => $currentStock,
                    'alert_quantity' => $product->alert_qty,
                    'predictions' => $predictions,
                    'recommended_order' => $predictions['recommended_reorder'],
                ]);
            }
        }

        return $productsNeedingReorder;
    }

    /**
     * Optimize purchase order quantities.
     *
     * @param int $businessId
     * @param array<int> $productIds
     * @return array
     */
    public function optimizePurchaseOrders(int $businessId, array $productIds = []): array
    {
        if (empty($productIds)) {
            $productsNeedingReorder = $this->getProductsNeedingReorder($businessId);
            $productIds = $productsNeedingReorder->pluck('product.id')->toArray();
        }

        $predictions = $this->generateBulkPredictions($productIds, $businessId, 30);
        
        $optimizedOrders = [];
        foreach ($predictions as $prediction) {
            $recommendedOrder = $prediction['recommended_reorder'];
            
            if ($recommendedOrder['should_reorder']) {
                $optimizedOrders[] = [
                    'product_id' => $prediction['product_id'],
                    'product_name' => $prediction['product_name'],
                    'recommended_quantity' => $recommendedOrder['recommended_order_quantity'],
                    'current_stock' => $recommendedOrder['current_stock'],
                    'reorder_point' => $recommendedOrder['reorder_point'],
                    'predicted_30_day_demand' => $prediction['total_predicted_demand'],
                ];
            }
        }

        return [
            'total_products' => count($optimizedOrders),
            'orders' => $optimizedOrders,
            'total_estimated_cost' => $this->estimateTotalCost($optimizedOrders, $businessId),
        ];
    }

    /**
     * Estimate total cost of purchase orders.
     *
     * @param array $orders
     * @param int $businessId
     * @return float
     */
    protected function estimateTotalCost(array $orders, int $businessId): float
    {
        $totalCost = 0;

        foreach ($orders as $order) {
            $product = Product::find($order['product_id']);
            if ($product) {
                $totalCost += $product->purchase_without_tax * $order['recommended_quantity'];
            }
        }

        return $totalCost;
    }
}