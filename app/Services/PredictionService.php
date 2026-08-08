<?php

namespace App\Services;

use App\Models\AutoOrderRule;
use App\Models\PredictionSetting;
use App\Models\Product;
use App\Models\SaleDetails;
use App\Models\SalesForecast;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PredictionService
{
    /**
     * Generate sales forecast for a specific product.
     *
     * @param  int|null  $forecastDays  Override default forecast days
     */
    public function forecastProduct(int $productId, int $businessId, ?int $forecastDays = null): array
    {
        $settings = PredictionSetting::getForBusiness($businessId);
        $product = Product::findOrFail($productId);
        $days = $forecastDays ?? $settings->forecast_days;
        $historicalMonths = $settings->historical_months;
        $method = $settings->prediction_method;

        // Get historical sales data
        $historicalSales = $this->getHistoricalSales($productId, $businessId, $historicalMonths);

        // Calculate daily average
        $dailyAverage = $this->calculateDailyAverage($historicalSales, $historicalMonths);

        // Calculate trend
        $trend = $this->calculateTrend($historicalSales);

        // Calculate seasonality
        $seasonalFactors = [];
        if ($settings->seasonal_adjustment) {
            $seasonalFactors = $this->calculateSeasonalFactors($historicalSales);
        }

        // Generate forecasts for each day
        $forecasts = [];
        $totalPredictedQty = 0;
        $totalPredictedRevenue = 0;

        for ($day = 1; $day <= $days; $day++) {
            $forecastDate = now()->addDays($day);

            // Base prediction from moving average
            $basePrediction = match ($method) {
                'moving_average' => $dailyAverage,
                'weighted_moving_average' => $this->weightedMovingAverage($historicalSales, 7),
                'exponential_smoothing' => $this->exponentialSmoothing($historicalSales),
                default => $this->combinedPrediction($historicalSales, $dailyAverage),
            };

            // Apply trend adjustment
            $trendAdjustment = $basePrediction * ($trend * $day / 30);
            $predictedQty = max(0, $basePrediction + $trendAdjustment);

            // Apply seasonal factor if available
            $dayOfWeek = $forecastDate->dayOfWeek;
            if (isset($seasonalFactors[$dayOfWeek])) {
                $predictedQty *= $seasonalFactors[$dayOfWeek];
            }

            // Calculate revenue
            $predictedRevenue = $predictedQty * ($product->sales_price ?? 0);

            // Calculate confidence (decreases further out we predict)
            $confidence = max(50, 95 - ($day * 1.5));

            // Calculate bounds (wider range for further dates)
            $stdDev = $this->calculateStdDev($historicalSales);
            $boundFactor = 1 + ($day * 0.02);
            $lowerBound = max(0, $predictedQty - ($stdDev * $boundFactor));
            $upperBound = $predictedQty + ($stdDev * $boundFactor);

            $forecasts[] = [
                'business_id' => $businessId,
                'product_id' => $productId,
                'forecast_date' => $forecastDate,
                'predicted_quantity' => round($predictedQty, 2),
                'predicted_revenue' => round($predictedRevenue, 2),
                'confidence_score' => round($confidence, 2),
                'lower_bound' => round($lowerBound, 2),
                'upper_bound' => round($upperBound, 2),
                'method_used' => $method,
                'factors' => json_encode([
                    'daily_average' => $dailyAverage,
                    'trend' => $trend,
                    'seasonal_factor' => $seasonalFactors[$dayOfWeek] ?? 1,
                    'std_dev' => $stdDev,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $totalPredictedQty += $predictedQty;
            $totalPredictedRevenue += $predictedRevenue;
        }

        // Bulk insert forecasts
        SalesForecast::where('business_id', $businessId)
            ->where('product_id', $productId)
            ->where('forecast_date', '>=', now()->startOfDay())
            ->update(['is_active' => false]);

        foreach ($forecasts as $forecast) {
            SalesForecast::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'product_id' => $productId,
                    'forecast_date' => $forecast['forecast_date'],
                ],
                $forecast
            );
        }

        return [
            'product_id' => $productId,
            'product_name' => $product->productName,
            'forecasts' => $forecasts,
            'summary' => [
                'total_predicted_qty' => round($totalPredictedQty, 2),
                'total_predicted_revenue' => round($totalPredictedRevenue, 2),
                'daily_average' => round($totalPredictedQty / $days, 2),
                'forecast_days' => $days,
                'method' => $method,
            ],
        ];
    }

    /**
     * Generate forecasts for multiple products (batch).
     */
    public function batchForecast(array $productIds, int $businessId): array
    {
        $results = [];

        foreach ($productIds as $productId) {
            try {
                $result = $this->forecastProduct($productId, $businessId);
                $results[] = $result;
            } catch (\Exception $e) {
                Log::error("Batch forecast error for product {$productId}: {$e->getMessage()}");
                $results[] = [
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Forecast all active products for a business.
     */
    public function forecastAllProducts(int $businessId): array
    {
        $productIds = Product::where('business_id', $businessId)
            ->pluck('id')
            ->toArray();

        return $this->batchForecast($productIds, $businessId);
    }

    /**
     * Calculate reorder point for a product.
     */
    public function calculateReorderPoint(int $productId, int $businessId): array
    {
        $settings = PredictionSetting::getForBusiness($businessId);
        $rule = AutoOrderRule::where('business_id', $businessId)
            ->where('product_id', $productId)
            ->first();

        $leadTime = $rule->lead_time_days ?? $settings->lead_time_days;
        $multiplier = $settings->safety_stock_multiplier;

        // Get average daily sales from last 3 months
        $historicalSales = $this->getHistoricalSales($productId, $businessId, 3);
        $dailyAverage = $this->calculateDailyAverage($historicalSales, 3);

        // Lead time demand
        $leadTimeDemand = $dailyAverage * $leadTime;

        // Calculate standard deviation of daily sales
        $stdDev = $this->calculateStdDev($historicalSales);

        // Safety stock = Z-score * StdDev * sqrt(Lead Time)
        // Using 1.65 as Z-score for 95% service level
        $safetyStock = 1.65 * $stdDev * sqrt($leadTime);
        $safetyStock = max(0, $safetyStock * $multiplier);

        // Reorder point = Lead Time Demand + Safety Stock
        $reorderPoint = $leadTimeDemand + $safetyStock;

        // Economic Order Quantity (EOQ)
        $annualDemand = $dailyAverage * 365;
        $orderingCost = 50; // assumed cost per order
        $holdingCost = ($historicalSales->avg('avg_price') ?? 10) * 0.2; // 20% of item cost
        $eoq = $holdingCost > 0 ? sqrt((2 * $annualDemand * $orderingCost) / $holdingCost) : 0;

        return [
            'product_id' => $productId,
            'daily_average_demand' => round($dailyAverage, 2),
            'lead_time_days' => $leadTime,
            'lead_time_demand' => round($leadTimeDemand, 2),
            'std_dev' => round($stdDev, 2),
            'safety_stock' => round($safetyStock, 2),
            'reorder_point' => round($reorderPoint, 2),
            'economic_order_qty' => round($eoq, 2),
            'service_level' => '95%',
        ];
    }

    /**
     * Get demand forecast report for a business.
     *
     * @param  string  $period  (daily, weekly, monthly)
     */
    public function getDemandReport(int $businessId, string $period = 'daily'): array
    {
        $settings = PredictionSetting::getForBusiness($businessId);
        $forecastDays = $settings->forecast_days;

        // Get all active forecasts grouped by product
        $forecasts = SalesForecast::where('business_id', $businessId)
            ->where('is_active', true)
            ->where('forecast_date', '>=', now()->startOfDay())
            ->where('forecast_date', '<=', now()->startOfDay()->addDays($forecastDays))
            ->with('product:id,productName,sales_price')
            ->get()
            ->groupBy('product_id');

        $report = [];
        foreach ($forecasts as $productId => $productForecasts) {
            $product = $productForecasts->first()->product;
            $currentStock = Stock::where('product_id', $productId)
                ->where('business_id', $businessId)
                ->sum('productStock');

            $totalPredicted = $productForecasts->sum('predicted_quantity');
            $avgConfidence = $productForecasts->avg('confidence_score');
            $avgPrice = $product->sales_price ?? 0;

            // Aggregate by period
            $periodData = match ($period) {
                'weekly' => $productForecasts->groupBy(fn ($f) => $f->forecast_date->weekOfYear),
                'monthly' => $productForecasts->groupBy(fn ($f) => $f->forecast_date->month),
                default => $productForecasts->keyBy(fn ($f) => $f->forecast_date->format('Y-m-d')),
            };

            // Calculate stock coverage
            $dailyAvg = $totalPredicted / $forecastDays;
            $stockCoverageDays = $dailyAvg > 0 ? $currentStock / $dailyAvg : 999;

            $report[] = [
                'product_id' => $productId,
                'product_name' => $product->productName,
                'current_stock' => (float) $currentStock,
                'total_predicted_demand' => round($totalPredicted, 2),
                'average_confidence' => round($avgConfidence, 2),
                'stock_coverage_days' => round($stockCoverageDays, 1),
                'needs_reorder' => $currentStock <= ($totalPredicted * 0.3), // less than 30% of forecast
                'period_data' => $periodData->map(function ($group, $key) {
                    $total = collect($group)->sum('predicted_quantity');

                    return [
                        'period' => $key,
                        'predicted_qty' => round($total, 2),
                        'avg_price' => round($group->avg('predicted_revenue') / max($total, 1), 2),
                    ];
                })->values(),
            ];
        }

        // Sort by need (lowest stock coverage first)
        usort($report, fn ($a, $b) => $a['stock_coverage_days'] <=> $b['stock_coverage_days']);

        return [
            'business_id' => $businessId,
            'period' => $period,
            'forecast_days' => $forecastDays,
            'products_count' => count($report),
            'products' => $report,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get historical sales data for a product.
     */
    private function getHistoricalSales(int $productId, int $businessId, int $months): Collection
    {
        $startDate = now()->subMonths($months)->startOfDay();

        return SaleDetails::select(
            'sale_details.product_id',
            'sale_details.price',
            'sale_details.quantities',
            'sale_details.purchase_price',
            'sales.saleDate'
        )
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->where('sale_details.product_id', $productId)
            ->where('sales.business_id', $businessId)
            ->where('sales.saleDate', '>=', $startDate)
            ->where('sales.saleDate', '<=', now())
            ->get()
            ->map(function ($item) {
                $item->sale_date = Carbon::parse($item->saleDate);

                return $item;
            });
    }

    /**
     * Calculate daily average sales.
     */
    private function calculateDailyAverage(Collection $sales, int $months): float
    {
        if ($sales->isEmpty()) {
            return 0;
        }

        $totalQty = $sales->sum('quantities');
        $daysInPeriod = max(1, $months * 30);

        return $totalQty / $daysInPeriod;
    }

    /**
     * Calculate sales trend (positive = increasing, negative = decreasing).
     */
    private function calculateTrend(Collection $sales): float
    {
        if ($sales->count() < 2) {
            return 0;
        }

        // Group by month
        $monthlySales = $sales->groupBy(fn ($item) => $item->sale_date->format('Y-m'))
            ->map(fn ($group) => $group->sum('quantities'));

        if ($monthlySales->count() < 2) {
            return 0;
        }

        $values = $monthlySales->values();
        $x = range(0, $values->count() - 1);
        $y = $values->toArray();

        // Simple linear regression
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        // Normalize slope as percentage of average
        $avg = $sumY / $n;

        return $avg > 0 ? $slope / $avg : 0;
    }

    /**
     * Calculate seasonal factors (by day of week).
     */
    private function calculateSeasonalFactors(Collection $sales): array
    {
        // Default: equal factors for all days
        $defaultFactors = [
            0 => 1.0, // Sunday
            1 => 1.0, // Monday
            2 => 1.0, // Tuesday
            3 => 1.0, // Wednesday
            4 => 1.0, // Thursday
            5 => 1.0, // Friday
            6 => 1.0, // Saturday
        ];

        if ($sales->isEmpty()) {
            return $defaultFactors;
        }

        // Group by day of week
        $daySales = $sales->groupBy(fn ($item) => $item->sale_date->dayOfWeek);

        $overallAvg = $sales->sum('quantities') / max(1, $sales->count());

        if ($overallAvg <= 0) {
            return $defaultFactors;
        }

        $factors = [];
        foreach (range(0, 6) as $day) {
            $dayGroup = $daySales->get($day, collect());
            $dayAvg = $dayGroup->count() > 0 ? $dayGroup->sum('quantities') / $dayGroup->count() : 0;
            $factors[$day] = $dayAvg > 0 ? round($dayAvg / $overallAvg, 4) : 0.5;
        }

        return $factors;
    }

    /**
     * Calculate weighted moving average (recent days have higher weight).
     */
    private function weightedMovingAverage(Collection $sales, int $windowDays = 7): float
    {
        if ($sales->isEmpty()) {
            return 0;
        }

        // Get last N days
        $recentSales = $sales->filter(fn ($item) => $item->sale_date >= now()->subDays($windowDays)
        );

        if ($recentSales->isEmpty()) {
            return $this->calculateDailyAverage($sales, 3);
        }

        // Group by date and sum quantities
        $dailySales = $recentSales->groupBy(fn ($item) => $item->sale_date->format('Y-m-d'))
            ->map(fn ($group) => $group->sum('quantities'));

        if ($dailySales->isEmpty()) {
            return 0;
        }

        $totalWeight = 0;
        $weightedSum = 0;
        $count = $dailySales->count();

        foreach ($dailySales as $date => $qty) {
            $daysAgo = now()->diffInDays(Carbon::parse($date));
            $weight = max(1, $count - $daysAgo); // More recent = higher weight
            $weightedSum += $qty * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    /**
     * Exponential smoothing forecast.
     */
    private function exponentialSmoothing(Collection $sales, float $alpha = 0.3): float
    {
        if ($sales->isEmpty()) {
            return 0;
        }

        // Sort by date
        $sorted = $sales->sortBy(fn ($item) => $item->sale_date);
        $values = $sorted->pluck('quantities')->toArray();

        if (empty($values)) {
            return 0;
        }

        // Simple Exponential Smoothing
        $smoothed = $values[0];

        for ($i = 1; $i < count($values); $i++) {
            $smoothed = $alpha * $values[$i] + (1 - $alpha) * $smoothed;
        }

        return $smoothed;
    }

    /**
     * Combined prediction method (average of multiple methods).
     */
    private function combinedPrediction(Collection $sales, float $dailyAverage): float
    {
        $ma = $dailyAverage;
        $wma = $this->weightedMovingAverage($sales, 7);
        $es = $this->exponentialSmoothing($sales);

        // Weighted average: MA (20%), WMA (50%), ES (30%)
        return ($ma * 0.2) + ($wma * 0.5) + ($es * 0.3);
    }

    /**
     * Calculate standard deviation of daily sales.
     */
    private function calculateStdDev(Collection $sales): float
    {
        if ($sales->count() < 2) {
            return 0;
        }

        // Group by date
        $dailySales = $sales->groupBy(fn ($item) => $item->sale_date->format('Y-m-d'))
            ->map(fn ($group) => $group->sum('quantities'));

        if ($dailySales->count() < 2) {
            return 0;
        }

        $values = $dailySales->values()->toArray();
        $mean = array_sum($values) / count($values);
        $sumSquaredDiff = 0;

        foreach ($values as $value) {
            $sumSquaredDiff += ($value - $mean) ** 2;
        }

        return sqrt($sumSquaredDiff / (count($values) - 1));
    }
}
