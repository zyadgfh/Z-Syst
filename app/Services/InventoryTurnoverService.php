<?php

namespace App\Services;

use App\Models\InventoryTurnoverReport;
use App\Models\Product;
use App\Models\ProductInventoryAnalysis;
use App\Models\PurchaseDetails;
use App\Models\SaleDetails;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryTurnoverService
{
    /**
     * Generate a comprehensive inventory turnover report.
     *
     * @param  string  $reportType  monthly, quarterly, yearly
     */
    public function generateReport(int $businessId, string $reportType = 'monthly', ?Carbon $customDate = null): array
    {
        $date = $customDate ?? now();
        [$periodStart, $periodEnd] = $this->getPeriodRange($reportType, $date);

        // Check if report already exists for this period
        $existingReport = InventoryTurnoverReport::where('business_id', $businessId)
            ->where('report_type', $reportType)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->first();

        if ($existingReport) {
            return $this->formatReportResult($existingReport);
        }

        // Get all products for this business
        $products = Product::where('business_id', $businessId)->get();
        $totalProducts = $products->count();

        $totalCogs = 0;
        $totalInventoryValue = 0;
        $slowMovingCount = 0;
        $deadStockCount = 0;
        $totalSlowMovingValue = 0;
        $totalDeadStockValue = 0;
        $productAnalyses = [];

        foreach ($products as $product) {
            $analysis = $this->analyzeProduct($product, $businessId, $periodStart, $periodEnd);
            $productAnalyses[] = $analysis;

            $totalCogs += $analysis['total_cogs'];
            $totalInventoryValue += $analysis['current_stock_value'];

            if ($analysis['movement_category'] === 'slow') {
                $slowMovingCount++;
                $totalSlowMovingValue += $analysis['current_stock_value'];
            } elseif ($analysis['movement_category'] === 'dead') {
                $deadStockCount++;
                $totalDeadStockValue += $analysis['current_stock_value'];
            }
        }

        // Calculate average inventory value
        $averageInventoryValue = $this->calculateAverageInventoryValue($businessId, $periodStart, $periodEnd);

        // Calculate turnover ratio and DIO
        $inventoryTurnoverRatio = $averageInventoryValue > 0
            ? round($totalCogs / $averageInventoryValue, 2)
            : 0;

        $daysInPeriod = $periodStart->diffInDays($periodEnd);
        $daysInventoryOutstanding = $inventoryTurnoverRatio > 0
            ? round($daysInPeriod / $inventoryTurnoverRatio, 2)
            : 0;

        // Save the report
        $report = InventoryTurnoverReport::create([
            'business_id' => $businessId,
            'report_type' => $reportType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_cogs' => round($totalCogs, 2),
            'average_inventory_value' => round($averageInventoryValue, 2),
            'inventory_turnover_ratio' => $inventoryTurnoverRatio,
            'days_inventory_outstanding' => $daysInventoryOutstanding,
            'total_products_analyzed' => $totalProducts,
            'slow_moving_count' => $slowMovingCount,
            'dead_stock_count' => $deadStockCount,
            'total_slow_moving_value' => round($totalSlowMovingValue, 2),
            'total_dead_stock_value' => round($totalDeadStockValue, 2),
            'total_inventory_value' => round($totalInventoryValue, 2),
            'meta' => json_encode([
                'period_label' => $this->getPeriodLabel($reportType, $periodStart, $periodEnd),
                'currency' => 'EGP',
            ]),
        ]);

        // Save individual product analyses
        foreach ($productAnalyses as $analysis) {
            ProductInventoryAnalysis::create([
                'business_id' => $businessId,
                'product_id' => $analysis['product_id'],
                'report_id' => $report->id,
                'total_quantity_sold' => $analysis['total_quantity_sold'],
                'total_sales_value' => $analysis['total_sales_value'],
                'total_cogs' => $analysis['total_cogs'],
                'average_stock_level' => $analysis['average_stock_level'],
                'turnover_ratio' => $analysis['turnover_ratio'],
                'days_inventory_outstanding' => $analysis['days_inventory_outstanding'],
                'abc_category' => $analysis['abc_category'],
                'movement_category' => $analysis['movement_category'],
                'current_stock_value' => $analysis['current_stock_value'],
                'current_stock_qty' => $analysis['current_stock_qty'],
                'stock_velocity' => $analysis['stock_velocity'],
            ]);
        }

        return $this->formatReportResult($report);
    }

    /**
     * Analyze a single product's inventory turnover.
     */
    public function analyzeProduct(Product $product, int $businessId, Carbon $periodStart, Carbon $periodEnd): array
    {
        // Get sales data for this product in the period
        $salesData = SaleDetails::join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->where('sale_details.product_id', $product->id)
            ->where('sales.business_id', $businessId)
            ->where('sales.saleDate', '>=', $periodStart)
            ->where('sales.saleDate', '<=', $periodEnd)
            ->select(
                DB::raw('COALESCE(SUM(sale_details.quantities), 0) as total_qty'),
                DB::raw('COALESCE(SUM(sale_details.quantities * sale_details.price), 0) as total_sales'),
                DB::raw('COALESCE(SUM(sale_details.quantities * sale_details.purchase_price), 0) as total_cogs')
            )
            ->first();

        $totalQuantitySold = (float) ($salesData->total_qty ?? 0);
        $totalSalesValue = (float) ($salesData->total_sales ?? 0);
        $totalCogs = (float) ($salesData->total_cogs ?? 0);

        // Get current stock
        $currentStockQty = (float) Stock::where('product_id', $product->id)
            ->where('business_id', $businessId)
            ->sum('productStock');

        $currentStockValue = $currentStockQty * (float) ($product->purchase_with_tax ?? $product->purchase_without_tax ?? 0);

        // Calculate average stock level during period
        $averageStockLevel = $this->calculateProductAverageStock($product->id, $businessId, $periodStart, $periodEnd);

        // Calculate turnover ratio
        $turnoverRatio = $averageStockLevel > 0
            ? round($totalCogs / max($averageStockLevel, 1), 2)
            : 0;

        // Calculate DIO
        $daysInPeriod = max(1, $periodStart->diffInDays($periodEnd));
        $daysInventoryOutstanding = $turnoverRatio > 0
            ? round($daysInPeriod / $turnoverRatio, 2)
            : ($totalQuantitySold > 0 ? $daysInPeriod : 999);

        // Calculate stock velocity (units sold per day)
        $stockVelocity = $daysInPeriod > 0
            ? round($totalQuantitySold / $daysInPeriod, 2)
            : 0;

        // Determine movement category
        $movementCategory = $this->determineMovementCategory($turnoverRatio, $stockVelocity, $daysInventoryOutstanding, $totalQuantitySold);

        // ABC analysis will be done after all products are analyzed
        $abcCategory = null;

        return [
            'product_id' => $product->id,
            'product_name' => $product->productName,
            'product_code' => $product->productCode,
            'total_quantity_sold' => $totalQuantitySold,
            'total_sales_value' => $totalSalesValue,
            'total_cogs' => $totalCogs,
            'average_stock_level' => round($averageStockLevel, 2),
            'turnover_ratio' => $turnoverRatio,
            'days_inventory_outstanding' => $daysInventoryOutstanding,
            'abc_category' => $abcCategory,
            'movement_category' => $movementCategory,
            'current_stock_value' => round($currentStockValue, 2),
            'current_stock_qty' => $currentStockQty,
            'stock_velocity' => $stockVelocity,
            'purchase_price' => (float) ($product->purchase_with_tax ?? $product->purchase_without_tax ?? 0),
            'sales_price' => (float) ($product->sales_price ?? 0),
        ];
    }

    /**
     * Perform ABC analysis on products based on their sales value contribution.
     */
    public function performABCAnalysis(int $businessId, Carbon $periodStart, Carbon $periodEnd): array
    {
        // Get all products with their sales data ordered by sales value descending
        $productSales = SaleDetails::join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->join('products', 'products.id', '=', 'sale_details.product_id')
            ->where('sales.business_id', $businessId)
            ->where('sales.saleDate', '>=', $periodStart)
            ->where('sales.saleDate', '<=', $periodEnd)
            ->select(
                'products.id as product_id',
                'products.productName',
                'products.productCode',
                DB::raw('COALESCE(SUM(sale_details.quantities * sale_details.price), 0) as total_sales_value'),
                DB::raw('COALESCE(SUM(sale_details.quantities), 0) as total_qty')
            )
            ->groupBy('products.id', 'products.productName', 'products.productCode')
            ->orderByDesc('total_sales_value')
            ->get();

        $totalValue = $productSales->sum('total_sales_value');

        // Calculate cumulative percentage
        $cumulativeValue = 0;
        $abcResults = [];

        foreach ($productSales as $index => $item) {
            $cumulativeValue += (float) $item->total_sales_value;
            $cumulativePercent = $totalValue > 0 ? ($cumulativeValue / $totalValue) * 100 : 0;

            // Determine ABC category
            if ($cumulativePercent <= 70) {
                $abcCategory = 'A';
            } elseif ($cumulativePercent <= 90) {
                $abcCategory = 'B';
            } else {
                $abcCategory = 'C';
            }

            $abcResults[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->productName,
                'product_code' => $item->productCode,
                'total_sales_value' => round((float) $item->total_sales_value, 2),
                'total_quantity' => (float) $item->total_qty,
                'percentage_of_total' => $totalValue > 0 ? round(((float) $item->total_sales_value / $totalValue) * 100, 2) : 0,
                'cumulative_percentage' => round($cumulativePercent, 2),
                'abc_category' => $abcCategory,
            ];
        }

        // Count per category
        $countA = count(array_filter($abcResults, fn ($r) => $r['abc_category'] === 'A'));
        $countB = count(array_filter($abcResults, fn ($r) => $r['abc_category'] === 'B'));
        $countC = count(array_filter($abcResults, fn ($r) => $r['abc_category'] === 'C'));

        $valueA = array_sum(array_column(array_filter($abcResults, fn ($r) => $r['abc_category'] === 'A'), 'total_sales_value'));
        $valueB = array_sum(array_column(array_filter($abcResults, fn ($r) => $r['abc_category'] === 'B'), 'total_sales_value'));
        $valueC = array_sum(array_column(array_filter($abcResults, fn ($r) => $r['abc_category'] === 'C'), 'total_sales_value'));

        return [
            'business_id' => $businessId,
            'total_value' => round($totalValue, 2),
            'categories' => [
                'A' => [
                    'count' => $countA,
                    'total_value' => round($valueA, 2),
                    'percentage' => $totalValue > 0 ? round(($valueA / $totalValue) * 100, 2) : 0,
                    'description' => 'منتجات ذات قيمة عالية - 70% من القيمة الإجمالية',
                ],
                'B' => [
                    'count' => $countB,
                    'total_value' => round($valueB, 2),
                    'percentage' => $totalValue > 0 ? round(($valueB / $totalValue) * 100, 2) : 0,
                    'description' => 'منتجات متوسطة القيمة - 20% من القيمة الإجمالية',
                ],
                'C' => [
                    'count' => $countC,
                    'total_value' => round($valueC, 2),
                    'percentage' => $totalValue > 0 ? round(($valueC / $totalValue) * 100, 2) : 0,
                    'description' => 'منتجات منخفضة القيمة - 10% من القيمة الإجمالية',
                ],
            ],
            'products' => $abcResults,
        ];
    }

    /**
     * Get slow-moving and dead stock products.
     *
     * @param  string  $category  slow or dead
     */
    public function getSlowMovingProducts(int $businessId, string $category = 'slow'): array
    {
        // Analyze all products over the last 6 months
        $periodEnd = now();
        $periodStart = now()->subMonths(6);

        $products = Product::where('business_id', $businessId)->get();
        $results = [];

        foreach ($products as $product) {
            $analysis = $this->analyzeProduct($product, $businessId, $periodStart, $periodEnd);

            if ($analysis['movement_category'] === $category || ($category === 'all' && in_array($analysis['movement_category'], ['slow', 'dead']))) {
                $results[] = $analysis;
            }
        }

        // Sort by DIO descending (worst first)
        usort($results, fn ($a, $b) => $b['days_inventory_outstanding'] <=> $a['days_inventory_outstanding']);

        $totalValue = array_sum(array_column($results, 'current_stock_value'));
        $totalQty = array_sum(array_column($results, 'current_stock_qty'));

        return [
            'business_id' => $businessId,
            'category' => $category,
            'period' => [
                'start' => $periodStart->format('Y-m-d'),
                'end' => $periodEnd->format('Y-m-d'),
            ],
            'total_products' => count($results),
            'total_inventory_value' => round($totalValue, 2),
            'total_inventory_qty' => $totalQty,
            'products' => $results,
        ];
    }

    /**
     * Get comprehensive inventory turnover summary with trends.
     */
    public function getSummary(int $businessId): array
    {
        // Get the latest report of each type
        $monthlyReport = InventoryTurnoverReport::where('business_id', $businessId)
            ->where('report_type', 'monthly')
            ->latest('period_end')
            ->first();

        $quarterlyReport = InventoryTurnoverReport::where('business_id', $businessId)
            ->where('report_type', 'quarterly')
            ->latest('period_end')
            ->first();

        $yearlyReport = InventoryTurnoverReport::where('business_id', $businessId)
            ->where('report_type', 'yearly')
            ->latest('period_end')
            ->first();

        // Quick stats without DB persistence
        $currentStockValue = (float) Stock::where('business_id', $businessId)
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->select(DB::raw('SUM(stocks.productStock * COALESCE(products.purchase_with_tax, products.purchase_without_tax, 0)) as total_value'))
            ->value('total_value') ?? 0;

        $totalProducts = Product::where('business_id', $businessId)->count();
        $totalStockQty = (float) Stock::where('business_id', $businessId)->sum('productStock');

        // Get movement distribution
        $movementDistribution = [
            'fast' => ProductInventoryAnalysis::where('business_id', $businessId)
                ->where('movement_category', 'fast')
                ->distinct('product_id')
                ->count('product_id'),
            'medium' => ProductInventoryAnalysis::where('business_id', $businessId)
                ->where('movement_category', 'medium')
                ->distinct('product_id')
                ->count('product_id'),
            'slow' => ProductInventoryAnalysis::where('business_id', $businessId)
                ->where('movement_category', 'slow')
                ->distinct('product_id')
                ->count('product_id'),
            'dead' => ProductInventoryAnalysis::where('business_id', $businessId)
                ->where('movement_category', 'dead')
                ->distinct('product_id')
                ->count('product_id'),
        ];

        // Get ABC distribution
        $abcDistribution = [
            'A' => ProductInventoryAnalysis::where('business_id', $businessId)
                ->where('abc_category', 'A')
                ->distinct('product_id')
                ->count('product_id'),
            'B' => ProductInventoryAnalysis::where('business_id', $businessId)
                ->where('abc_category', 'B')
                ->distinct('product_id')
                ->count('product_id'),
            'C' => ProductInventoryAnalysis::where('business_id', $businessId)
                ->where('abc_category', 'C')
                ->distinct('product_id')
                ->count('product_id'),
        ];

        return [
            'business_id' => $businessId,
            'total_products' => $totalProducts,
            'total_stock_qty' => $totalStockQty,
            'total_inventory_value' => round($currentStockValue, 2),
            'reports_available' => [
                'monthly' => $monthlyReport ? $this->formatReportResult($monthlyReport) : null,
                'quarterly' => $quarterlyReport ? $this->formatReportResult($quarterlyReport) : null,
                'yearly' => $yearlyReport ? $this->formatReportResult($yearlyReport) : null,
            ],
            'movement_distribution' => $movementDistribution,
            'abc_distribution' => $abcDistribution,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Calculate average inventory value for a business over a period.
     * Uses opening and closing stock values with a simple average.
     */
    private function calculateAverageInventoryValue(int $businessId, Carbon $periodStart, Carbon $periodEnd): float
    {
        // Get inventory value at start of period
        $openingStockValue = $this->getInventoryValueAtDate($businessId, $periodStart);

        // Get inventory value at end of period
        $closingStockValue = $this->getInventoryValueAtDate($businessId, $periodEnd);

        // Simple average of opening and closing
        return ($openingStockValue + $closingStockValue) / 2;
    }

    /**
     * Get total inventory value at a specific date.
     */
    private function getInventoryValueAtDate(int $businessId, Carbon $date): float
    {
        // Get stock that existed before or on this date
        // This is approximate - we sum all purchase quantities up to the date
        // and subtract all sales quantities up to the date

        $purchasedQty = (float) PurchaseDetails::whereHas('purchase', function ($q) use ($businessId, $date) {
            $q->where('business_id', $businessId)
                ->where('purchaseDate', '<=', $date);
        })->sum('quantities');

        $soldQty = (float) SaleDetails::whereHas('sale', function ($q) use ($businessId, $date) {
            $q->where('business_id', $businessId)
                ->where('saleDate', '<=', $date);
        })->sum('quantities');

        $netStock = max(0, $purchasedQty - $soldQty);

        // Get average purchase price
        $avgPurchasePrice = (float) Product::where('business_id', $businessId)
            ->select(DB::raw('AVG(COALESCE(purchase_with_tax, purchase_without_tax, 0)) as avg_price'))
            ->value('avg_price') ?? 0;

        return $netStock * $avgPurchasePrice;
    }

    /**
     * Calculate average stock level for a specific product during the period.
     * Uses monthly snapshots for approximation.
     */
    private function calculateProductAverageStock(int $productId, int $businessId, Carbon $start, Carbon $end): float
    {
        // Get current stock
        $currentStock = (float) Stock::where('product_id', $productId)
            ->where('business_id', $businessId)
            ->sum('productStock');

        // Get total purchases during period
        $purchases = (float) PurchaseDetails::where('product_id', $productId)
            ->whereHas('purchase', function ($q) use ($businessId, $start, $end) {
                $q->where('business_id', $businessId)
                    ->whereBetween('purchaseDate', [$start, $end]);
            })
            ->sum('quantities');

        // Get total sales during period
        $sales = (float) SaleDetails::where('product_id', $productId)
            ->whereHas('sale', function ($q) use ($businessId, $start, $end) {
                $q->where('business_id', $businessId)
                    ->whereBetween('saleDate', [$start, $end]);
            })
            ->sum('quantities');

        // Estimate opening stock = current + sales - purchases
        $openingStock = $currentStock + $sales - $purchases;

        // Average of opening and closing stock
        return max(0, ($openingStock + $currentStock) / 2);
    }

    /**
     * Determine the movement category for a product.
     */
    private function determineMovementCategory(float $turnoverRatio, float $stockVelocity, float $dio, float $totalQuantitySold): string
    {
        // If no sales at all, it's dead stock
        if ($totalQuantitySold <= 0) {
            return 'dead';
        }

        // If very slow turnover or very high DIO (> 6 months)
        if ($dio > 180) {
            return 'dead';
        }

        // If turnover is low or DIO is high (3-6 months)
        if ($dio > 90 || $turnoverRatio < 0.5) {
            return 'slow';
        }

        // If moderate turnover (1-3 months coverage)
        if ($dio > 30 || $turnoverRatio < 2) {
            return 'medium';
        }

        // Fast moving (high turnover, low DIO)
        return 'fast';
    }

    /**
     * Get the date range for a given report type.
     */
    private function getPeriodRange(string $reportType, Carbon $date): array
    {
        return match ($reportType) {
            'monthly' => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ],
            'quarterly' => [
                $date->copy()->startOfQuarter(),
                $date->copy()->endOfQuarter(),
            ],
            'yearly' => [
                $date->copy()->startOfYear(),
                $date->copy()->endOfYear(),
            ],
            'custom' => [
                $date->copy()->subDays(30),
                $date,
            ],
            default => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Get a human-readable label for the period.
     */
    private function getPeriodLabel(string $reportType, Carbon $start, Carbon $end): string
    {
        $months = [
            'January' => 'يناير', 'February' => 'فبراير', 'March' => 'مارس',
            'April' => 'إبريل', 'May' => 'مايو', 'June' => 'يونيو',
            'July' => 'يوليو', 'August' => 'أغسطس', 'September' => 'سبتمبر',
            'October' => 'أكتوبر', 'November' => 'نوفمبر', 'December' => 'ديسمبر',
        ];

        $startMonth = $months[$start->format('F')] ?? $start->format('F');
        $endMonth = $months[$end->format('F')] ?? $end->format('F');

        return match ($reportType) {
            'monthly' => "{$startMonth} {$start->format('Y')}",
            'quarterly' => "الربع {$start->quarter} - {$start->format('Y')}",
            'yearly' => "السنة {$start->format('Y')}",
            default => "{$startMonth} {$start->format('Y')} - {$endMonth} {$end->format('Y')}",
        };
    }

    /**
     * Format the report result for API response.
     */
    private function formatReportResult(InventoryTurnoverReport $report): array
    {
        $meta = is_string($report->meta) ? json_decode($report->meta, true) : $report->meta;

        return [
            'id' => $report->id,
            'report_type' => $report->report_type,
            'period_start' => $report->period_start->format('Y-m-d'),
            'period_end' => $report->period_end->format('Y-m-d'),
            'period_label' => $meta['period_label'] ?? '',
            'total_cogs' => (float) $report->total_cogs,
            'average_inventory_value' => (float) $report->average_inventory_value,
            'inventory_turnover_ratio' => (float) $report->inventory_turnover_ratio,
            'days_inventory_outstanding' => (float) $report->days_inventory_outstanding,
            'total_products_analyzed' => $report->total_products_analyzed,
            'slow_moving_count' => $report->slow_moving_count,
            'dead_stock_count' => $report->dead_stock_count,
            'total_slow_moving_value' => (float) $report->total_slow_moving_value,
            'total_dead_stock_value' => (float) $report->total_dead_stock_value,
            'total_inventory_value' => (float) $report->total_inventory_value,
            'created_at' => $report->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
