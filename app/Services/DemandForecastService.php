<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DemandForecastService
{
    /**
     * Get reorder suggestions for products
     */
    public function getReorderSuggestions(int $companyId, int $branchId = null, int $limit = 20): array
    {
        $query = ProductStock::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->with('product');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stocks = $query->get();
        $suggestions = [];

        foreach ($stocks as $stock) {
            $dailyAverage = $this->calculateDailyAverage($companyId, $stock->product_id, 30);
            
            // Calculate days of supply remaining
            $daysOfSupply = $dailyAverage > 0 
                ? floor($stock->quantity / $dailyAverage) 
                : 999;

            // If days of supply < 14 days, suggest reorder
            if ($daysOfSupply < 14 || $stock->quantity <= $stock->reorder_level) {
                $suggestedQuantity = $this->calculateSuggestedQuantity(
                    $stock->reorder_quantity,
                    $dailyAverage,
                    $daysOfSupply
                );

                $suggestions[] = [
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product->productName ?? 'Unknown',
                    'product_code' => $stock->product->productCode ?? null,
                    'current_stock' => $stock->quantity,
                    'reorder_level' => $stock->reorder_level,
                    'daily_average_sales' => round($dailyAverage, 2),
                    'days_of_supply' => $daysOfSupply,
                    'suggested_quantity' => $suggestedQuantity,
                    'urgency' => $this->calculateUrgency($daysOfSupply),
                    'last_sold_at' => $this->getLastSoldDate($companyId, $stock->product_id),
                ];
            }
        }

        // Sort by urgency and take limit
        usort($suggestions, function ($a, $b) {
            $urgencyOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return ($urgencyOrder[$a['urgency']] ?? 4) <=> ($urgencyOrder[$b['urgency']] ?? 4);
        });

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Calculate daily average sales for a product
     */
    protected function calculateDailyAverage(int $companyId, int $productId, int $days = 30): float
    {
        $startDate = Carbon::now()->subDays($days);

        $totalSold = SaleItem::whereHas('sale', function ($q) use ($companyId, $startDate) {
            $q->where('company_id', $companyId)
              ->where('created_at', '>=', $startDate);
        })
        ->where('product_id', $productId)
        ->sum('quantity');

        return $totalSold / $days;
    }

    /**
     * Calculate suggested reorder quantity
     */
    protected function calculateSuggestedQuantity(int $reorderQuantity, float $dailyAverage, int $daysOfSupply): int
    {
        // Suggest enough for 30 days of sales plus safety stock
        $safetyStock = max(10, $reorderQuantity * 0.2);
        $suggestedQty = (int) ceil($dailyAverage * 30 + $safetyStock);
        
        return max($suggestedQty, $reorderQuantity);
    }

    /**
     * Calculate urgency level
     */
    protected function calculateUrgency(int $daysOfSupply): string
    {
        if ($daysOfSupply <= 3) {
            return 'critical';
        } elseif ($daysOfSupply <= 7) {
            return 'high';
        } elseif ($daysOfSupply <= 14) {
            return 'medium';
        }
        
        return 'low';
    }

    /**
     * Get last sold date for a product
     */
    protected function getLastSoldDate(int $companyId, int $productId): ?string
    {
        $lastSale = SaleItem::whereHas('sale', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->where('product_id', $productId)
        ->latest()
        ->first();

        return $lastSale?->created_at?->format('Y-m-d H:i:s');
    }

    /**
     * Get forecast for specific period
     */
    public function getForecast(int $companyId, int $days = 30, int $branchId = null): array
    {
        $fastMovingProducts = $this->getFastMovingProducts($companyId, $days, $branchId);
        
        return [
            'period_days' => $days,
            'products' => $fastMovingProducts,
            'total_reorder_value' => $this->calculateTotalReorderValue($fastMovingProducts),
        ];
    }

    /**
     * Get fast moving products with forecast
     */
    protected function getFastMovingProducts(int $companyId, int $days, int $branchId = null): array
    {
        $startDate = Carbon::now()->subDays($days);

        $query = SaleItem::select([
            'product_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('COUNT(DISTINCT sale_id) as total_sales'),
            DB::raw('AVG(total) as avg_sale_value'),
        ])
        ->whereHas('sale', function ($q) use ($companyId, $startDate, $branchId) {
            $q->where('company_id', $companyId)
              ->where('created_at', '>=', $startDate);
            
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })
        ->with('product:id,productName,productCode,sales_price,purchase_with_tax')
        ->groupBy('product_id')
        ->orderBy('total_quantity', 'desc')
        ->limit(50)
        ->get();

        return $query->map(function ($item) {
            $dailyAvg = $item->total_quantity / 30; // Assuming 30 days
            
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->productName ?? 'Unknown',
                'product_code' => $item->product->productCode ?? null,
                'total_quantity_sold' => $item->total_quantity,
                'total_sales' => $item->total_sales,
                'avg_sale_value' => $item->avg_sale_value,
                'daily_average' => round($dailyAvg, 2),
                'predicted_30_days' => round($dailyAvg * 30, 2),
                'predicted_90_days' => round($dailyAvg * 90, 2),
            ];
        })->toArray();
    }

    /**
     * Calculate total reorder value
     */
    protected function calculateTotalReorderValue(array $products): float
    {
        return collect($products)->sum(function ($product) {
            return $product['suggested_quantity'] ?? 0 * ($product['product']?->purchase_with_tax ?? 0);
        });
    }

    /**
     * Get seasonal trends (if applicable)
     */
    public function getSeasonalTrends(int $companyId): array
    {
        $currentMonth = Carbon::now()->month;
        $previousMonth = $currentMonth - 1 > 0 ? $currentMonth - 1 : 12;

        $currentSales = SaleItem::whereHas('sale', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->whereMonth('created_at', Carbon::now()->month);
        })->sum('quantity');

        $previousSales = SaleItem::whereHas('sale', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->whereMonth('created_at', $previousMonth);
        })->sum('quantity');

        $trend = $previousSales > 0 
            ? (($currentSales - $previousSales) / $previousSales) * 100 
            : 0;

        return [
            'current_month_sales' => $currentSales,
            'previous_month_sales' => $previousSales,
            'trend_percentage' => round($trend, 2),
            'trend_direction' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable'),
        ];
    }
}