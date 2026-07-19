<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PrescriptionItem;
use App\Models\Product;
use Illuminate\Support\Collection;

class ForecastingService
{
    /**
     * Generate demand forecast for products based on dispensed history.
     *
     * @param int $companyId Company ID for tenant isolation
     * @param int $windowDays Forecast window in days (7-90)
     * @param int $limit Maximum number of products to return
     * @return array
     */
    public function generateDemandForecast(int $companyId, int $windowDays = 30, int $limit = 10): array
    {
        $windowDays = max(7, min(90, $windowDays));
        $from = now()->subDays($windowDays);
        $bucketDays = 7; // Weekly buckets
        $bucketsCount = (int) ceil($windowDays / $bucketDays);

        $dispensedLines = $this->getDispensedHistory($companyId, $from, $windowDays);

        $forecastByProduct = $this->calculateForecastByProduct(
            $dispensedLines,
            $bucketsCount,
            $bucketDays,
            $windowDays
        );

        $sorted = $forecastByProduct
            ->sortByDesc('average_daily_demand')
            ->take($limit)
            ->values();

        return [
            'forecast_window_days' => $windowDays,
            'generated_at' => now()->toDateTimeString(),
            'total_products_forecasted' => $forecastByProduct->count(),
            'items' => $sorted->toArray(),
        ];
    }

    /**
     * Get dispensed history from prescription items.
     *
     * Uses FEFO logic: first expiry-first-out for stock deduction.
     */
    private function getDispensedHistory(int $companyId, $from, int $windowDays): Collection
    {
        return PrescriptionItem::query()
            ->whereHas('prescription', function ($query) use ($companyId, $from) {
                $query->where('company_id', $companyId)
                    ->where('updated_at', '>=', $from);
            })
            ->with(['product:id,productName,barcode'])
            ->where('dispensed_quantity', '>', 0)
            ->get(['prescription_id', 'product_id', 'dispensed_quantity', 'updated_at'])
            ->map(function ($line) {
                return (object) [
                    'product_id' => $line->product_id,
                    'quantity' => (float) $line->dispensed_quantity,
                    'ts' => $line->updated_at,
                    'source' => 'prescription_item',
                    'product' => $line->product,
                ];
            });
    }

    /**
     * Calculate forecast metrics for each product.
     */
    private function calculateForecastByProduct(Collection $dispensedLines, int $bucketsCount, int $bucketDays, int $windowDays): Collection
    {
        return $dispensedLines
            ->groupBy('product_id')
            ->map(function ($lines, $productId) use ($bucketsCount, $bucketDays, $windowDays) {
                $product = $lines->first()?->product;
                $productName = $product?->productName ?? 'Unknown';
                $barcode = $product?->barcode;

                // Calculate bucket totals for trend analysis
                $bucketTotals = array_fill(0, $bucketsCount, 0.0);
                $from = now()->subDays($windowDays);

                foreach ($lines as $line) {
                    $ts = $line->ts;
                    if (!$ts) continue;

                    $deltaDays = max(0, (int) $from->diffInDays($ts));
                    $idx = (int) floor($deltaDays / $bucketDays);
                    $idx = max(0, min($bucketsCount - 1, $idx));
                    $bucketTotals[$idx] += (float) $line->quantity;
                }

                $totalDispensed = (float) array_sum($bucketTotals);
                $averageDailyDemand = $windowDays > 0 ? round($totalDispensed / $windowDays, 2) : 0.0;

                // Trend calculation: compare recent half vs previous half
                $trend = $this->calculateTrend($bucketTotals, $bucketDays);

                // Reorder recommendation with trend adjustment
                $recommendedReorder = $this->calculateReorderQuantity($averageDailyDemand, $trend);

                // Safety stock based on demand volatility
                $safetyStock = $this->calculateSafetyStock($averageDailyDemand, $bucketTotals, $bucketsCount);

                // Confidence score based on volume and coverage
                $confidence = $this->calculateConfidence($totalDispensed, $bucketTotals, $bucketsCount);

                return [
                    'product_id' => (int) $productId,
                    'product_name' => $productName,
                    'barcode' => $barcode,
                    'forecast_window_days' => $windowDays,
                    'average_daily_demand' => $averageDailyDemand,
                    'recommended_reorder_quantity' => $recommendedReorder,
                    'safety_stock' => $safetyStock,
                    'confidence' => $confidence,
                    'trend' => $trend['direction'],
                ];
            });
    }

    /**
     * Calculate trend direction and multiplier.
     */
    private function calculateTrend(array $bucketTotals, int $bucketDays): array
    {
        $bucketsCount = count($bucketTotals);
        $half = max(1, intdiv($bucketsCount, 2));

        $prevBuckets = array_slice($bucketTotals, 0, $half);
        $lastBuckets = array_slice($bucketTotals, $bucketsCount - $half);

        $prevAvgDaily = max(0.0001, array_sum($prevBuckets) / (count($prevBuckets) * $bucketDays));
        $lastAvgDaily = max(0.0001, array_sum($lastBuckets) / (count($lastBuckets) * $bucketDays));

        $trendRatio = $lastAvgDaily / $prevAvgDaily;
        $trendThreshold = 0.08; // 8% threshold for flat trend

        if (abs($trendRatio - 1.0) < $trendThreshold) {
            $direction = 'flat';
            $multiplier = 1.0;
        } elseif ($trendRatio > 1.0) {
            $direction = 'up';
            $multiplier = 1.15; // Increase reorder by 15% for upward trend
        } else {
            $direction = 'down';
            $multiplier = 0.9; // Decrease reorder by 10% for downward trend
        }

        return [
            'direction' => $direction,
            'multiplier' => $multiplier,
            'ratio' => $trendRatio,
        ];
    }

    /**
     * Calculate recommended reorder quantity.
     */
    private function calculateReorderQuantity(float $averageDailyDemand, array $trend): int
    {
        $buffer = max(1, (int) round($averageDailyDemand * 0.25)); // 25% buffer
        $reorderBase = $averageDailyDemand + $buffer;
        $recommendedReorder = max(1, (int) ceil($reorderBase * $trend['multiplier']));

        return $recommendedReorder;
    }

    /**
     * Calculate safety stock based on volatility.
     */
    private function calculateSafetyStock(float $averageDailyDemand, array $bucketTotals, int $bucketsCount): int
    {
        $nonZeroBuckets = count(array_filter($bucketTotals, fn($v) => $v > 0));
        $volatilityFactor = $bucketsCount > 0 ? ($nonZeroBuckets / $bucketsCount) : 0;

        // Higher volatility = more safety stock needed
        $safetyStock = max(1, (int) round($averageDailyDemand * (0.35 + 0.4 * $volatilityFactor)));

        return $safetyStock;
    }

    /**
     * Calculate confidence score.
     */
    private function calculateConfidence(float $totalDispensed, array $bucketTotals, int $bucketsCount): string
    {
        $nonZeroBuckets = count(array_filter($bucketTotals, fn($v) => $v > 0));

        // Score based on volume and coverage
        $volumeScore = ($totalDispensed >= 60 ? 2 : ($totalDispensed >= 25 ? 1 : 0));
        $coverageScore = ($nonZeroBuckets >= max(2, intdiv($bucketsCount, 2)) ? 1 : 0);
        $confidenceScore = $volumeScore + $coverageScore;

        if ($confidenceScore >= 2) {
            return 'high';
        } elseif ($confidenceScore >= 1) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get products with low stock alerts.
     */
    public function getLowStockAlerts(int $companyId, int $limit = 10): array
    {
        $lowStockProducts = \App\Models\Stock::query()
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->where('products.company_id', $companyId)
            ->whereColumn('stocks.productStock', '<=', 'products.alert_qty')
            ->select('stocks.*', 'products.alert_qty')
            ->with('product:id,productName')
            ->limit($limit)
            ->get();

        return $lowStockProducts->map(fn($stock) => [
            'product_id' => $stock->product_id,
            'name' => $stock->product?->productName ?? 'Unknown',
            'stock' => (int) $stock->productStock,
            'alert_qty' => (int) ($stock->alert_qty ?? 0),
        ])->values()->toArray();
    }

    /**
     * Generate aggregated demand forecast for Excel export.
     */
    public function generateExportData(int $companyId, int $windowDays = 30): array
    {
        $forecast = $this->generateDemandForecast($companyId, $windowDays, 100); // Get all products

        return collect($forecast['items'])->map(function ($item) {
            return [
                'المنتج' => $item['product_name'],
                'الباركود' => $item['barcode'],
                'متوسط الطلب اليومي' => $item['average_daily_demand'],
                'كمية إعادة الطلب المقترحة' => $item['recommended_reorder_quantity'],
                'المخزون الآمن' => $item['safety_stock'],
                'الثقة' => $item['confidence'],
                'الاتجاه' => $item['trend'],
            ];
        })->toArray();
    }
}