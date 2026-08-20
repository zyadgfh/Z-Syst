<?php

namespace App\Services;

use App\Models\Business;
use App\Models\LoyaltyTransaction;
use App\Models\Party;
use App\Models\PlanSubscribe;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\RecallEvent;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardReportService
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get overall dashboard statistics with caching
     */
    public function getOverallStatistics(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $cacheKey = "statistics:{$businessId}:{$dateFrom}:{$dateTo}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $dateFrom, $dateTo) {
            return [
                'revenue' => $this->getRevenueStatistics($businessId, $dateFrom, $dateTo),
                'sales' => $this->getSalesStatistics($businessId, $dateFrom, $dateTo),
                'purchases' => $this->getPurchaseStatistics($businessId, $dateFrom, $dateTo),
                'inventory' => $this->getInventoryStatistics($businessId),
                'customers' => $this->getCustomerStatistics($businessId),
                'subscriptions' => $this->getSubscriptionStatistics($dateFrom, $dateTo),
            ];
        });
    }

    /**
     * Get revenue statistics - Optimized with single query
     */
    protected function getRevenueStatistics(?int $businessId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $query = Sale::whereBetween('saleDate', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        // Use aggregate query instead of fetching all records
        $stats = $query->selectRaw('
            COUNT(*) as total_sales,
            COALESCE(SUM(totalAmount), 0) as total_revenue,
            COALESCE(SUM(paidAmount), 0) as total_paid,
            COALESCE(SUM(dueAmount), 0) as total_due
        ')->first();

        return [
            'total_revenue' => $stats->total_revenue,
            'total_sales' => $stats->total_sales,
            'average_order_value' => $stats->total_sales > 0 ? $stats->total_revenue / $stats->total_sales : 0,
            'total_paid' => $stats->total_paid,
            'total_due' => $stats->total_due,
        ];
    }

    /**
     * Get sales statistics - Optimized with single query
     */
    protected function getSalesStatistics(?int $businessId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $query = Sale::whereBetween('saleDate', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        // Use aggregate query with groupBy for payment types
        $stats = $query->selectRaw('
            COUNT(*) as total_sales,
            COALESCE(SUM(totalQuantity), 0) as total_quantity,
            COALESCE(SUM(discount), 0) as total_discount,
            paymentType
        ')->groupBy('paymentType')->get();

        return [
            'total_sales' => $stats->sum('total_sales'),
            'total_quantity' => $stats->sum('total_quantity'),
            'total_discount' => $stats->sum('total_discount'),
            'sales_by_payment_type' => $stats->pluck('total_sales', 'paymentType'),
        ];
    }

    /**
     * Get purchase statistics - Optimized with single query
     */
    protected function getPurchaseStatistics(?int $businessId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $query = Purchase::whereBetween('purchaseDate', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_purchases,
            COALESCE(SUM(totalAmount), 0) as total_purchase_amount,
            COALESCE(SUM(paidAmount), 0) as total_paid,
            COALESCE(SUM(dueAmount), 0) as total_due
        ')->first();

        return [
            'total_purchases' => $stats->total_purchases,
            'total_purchase_amount' => $stats->total_purchase_amount,
            'total_paid' => $stats->total_paid,
            'total_due' => $stats->total_due,
        ];
    }

    /**
     * Get inventory statistics - Optimized with single query
     */
    protected function getInventoryStatistics(?int $businessId): array
    {
        $query = Product::query();

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_products,
            COALESCE(SUM(stock), 0) as total_stock,
            SUM(CASE WHEN stock <= 10 THEN 1 ELSE 0 END) as low_stock_products,
            SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out_of_stock_products
        ')->first();

        // Get products by category
        $categoryQuery = Product::query();
        if ($businessId) {
            $categoryQuery->where('business_id', $businessId);
        }
        $categoryStats = $categoryQuery->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->pluck('count', 'category_id');

        return [
            'total_products' => $stats->total_products,
            'total_stock' => $stats->total_stock,
            'low_stock_products' => $stats->low_stock_products,
            'out_of_stock_products' => $stats->out_of_stock_products,
            'products_by_category' => $categoryStats,
        ];
    }

    /**
     * Get customer statistics - Optimized with single query
     */
    protected function getCustomerStatistics(?int $businessId): array
    {
        $query = Party::where('type', 'customer');

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_customers,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_customers,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive_customers
        ')->first();

        return [
            'total_customers' => $stats->total_customers,
            'active_customers' => $stats->active_customers,
            'inactive_customers' => $stats->inactive_customers,
        ];
    }

    /**
     * Get subscription statistics
     */
    protected function getSubscriptionStatistics(Carbon $dateFrom, Carbon $dateTo): array
    {
        $cacheKey = "subscriptions:stats:{$dateFrom}:{$dateTo}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($dateFrom, $dateTo) {
            $subscriptions = PlanSubscribe::whereBetween('created_at', [$dateFrom, $dateTo])->get();

            return [
                'total_subscriptions' => $subscriptions->count(),
                'total_revenue' => $subscriptions->sum('price'),
                'active_subscriptions' => Business::where('will_expire', '>', now())->count(),
                'expired_subscriptions' => Business::where('will_expire', '<', now())->count(),
            ];
        });
    }

    /**
     * Get sales by date chart data - Optimized with cache
     */
    public function getSalesByDate(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();
        $groupBy = $filters['group_by'] ?? 'day';

        $cacheKey = "sales_by_date:{$businessId}:{$dateFrom}:{$dateTo}:{$groupBy}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $dateFrom, $dateTo, $groupBy) {
            $dateFormat = match ($groupBy) {
                'hour' => '%Y-%m-%d %H:00',
                'day' => '%Y-%m-%d',
                'week' => '%x-W%v',
                'month' => '%Y-%m',
                default => '%Y-%m-%d',
            };

            $query = Sale::whereBetween('saleDate', [$dateFrom, $dateTo])
                ->selectRaw("
                    DATE_FORMAT(saleDate, '{$dateFormat}') as date_key,
                    COUNT(*) as count,
                    COALESCE(SUM(totalAmount), 0) as revenue
                ")
                ->groupBy('date_key')
                ->orderBy('date_key');

            if ($businessId) {
                $query->where('business_id', $businessId);
            }

            return $query->get()->pluck('revenue', 'date_key')->toArray();
        });
    }

    /**
     * Get top selling products - Optimized with cache
     */
    public function getTopSellingProducts(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();
        $limit = $filters['limit'] ?? 10;

        $cacheKey = "top_products:{$businessId}:{$dateFrom}:{$dateTo}:{$limit}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $dateFrom, $dateTo, $limit) {
            $query = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.productName as name',
                    DB::raw('SUM(sale_details.quantities) as total_quantity'),
                    DB::raw('SUM(sale_details.price * sale_details.quantities) as total_revenue')
                )
                ->whereBetween('sales.saleDate', [$dateFrom, $dateTo])
                ->groupBy('products.id', 'products.productName')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit);

            if ($businessId) {
                $query->where('sales.business_id', $businessId);
            }

            return $query->get()->toArray();
        });
    }

    /**
     * Get top customers - Optimized with cache
     */
    public function getTopCustomers(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();
        $limit = $filters['limit'] ?? 10;

        $cacheKey = "top_customers:{$businessId}:{$dateFrom}:{$dateTo}:{$limit}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $dateFrom, $dateTo, $limit) {
            $query = Sale::whereBetween('saleDate', [$dateFrom, $dateTo])
                ->select(
                    'party_id',
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('COALESCE(SUM(totalAmount), 0) as total_spent')
                )
                ->groupBy('party_id')
                ->orderBy('total_spent', 'desc')
                ->limit($limit);

            if ($businessId) {
                $query->where('business_id', $businessId);
            }

            $sales = $query->get();

            // Get party names in single query (N+1 fix)
            $partyIds = $sales->pluck('party_id')->filter()->unique();
            $parties = Party::whereIn('id', $partyIds)->pluck('name', 'id');

            return $sales->map(function ($sale) use ($parties) {
                return [
                    'customer_id' => $sale->party_id,
                    'customer_name' => $parties->get($sale->party_id, 'Unknown'),
                    'total_orders' => $sale->total_orders,
                    'total_spent' => $sale->total_spent,
                ];
            })->toArray();
        });
    }

    /**
     * Get profit and loss report
     */
    public function getProfitAndLoss(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $cacheKey = "profit_loss:{$businessId}:{$dateFrom}:{$dateTo}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $dateFrom, $dateTo) {
            $salesQuery = Sale::whereBetween('saleDate', [$dateFrom, $dateTo]);
            $purchasesQuery = Purchase::whereBetween('purchaseDate', [$dateFrom, $dateTo]);

            if ($businessId) {
                $salesQuery->where('business_id', $businessId);
                $purchasesQuery->where('business_id', $businessId);
            }

            $totalSales = $salesQuery->sum('totalAmount');
            $totalPurchases = $purchasesQuery->sum('totalAmount');
            $grossProfit = $totalSales - $totalPurchases;

            return [
                'total_sales' => $totalSales,
                'total_purchases' => $totalPurchases,
                'gross_profit' => $grossProfit,
                'profit_margin' => $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0,
            ];
        });
    }

    /**
     * Get warehouse statistics
     */
    public function getWarehouseStatistics(?int $businessId): array
    {
        $cacheKey = "warehouse_stats:{$businessId}";

        return $this->cacheService->remember($cacheKey, 600, function () use ($businessId) {
            $query = Warehouse::query();

            if ($businessId) {
                $query->where('business_id', $businessId);
            }

            $warehouses = $query->get();

            return [
                'total_warehouses' => $warehouses->count(),
                'active_warehouses' => $warehouses->where('is_active', true)->count(),
                'warehouses' => $warehouses->map(function ($warehouse) {
                    return [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'location' => $warehouse->location,
                        'is_active' => $warehouse->is_active,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * Get transfer statistics
     */
    public function getTransferStatistics(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $cacheKey = "transfer_stats:{$businessId}:{$dateFrom}:{$dateTo}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $dateFrom, $dateTo) {
            $query = StockTransfer::whereBetween('transfer_date', [$dateFrom, $dateTo]);

            if ($businessId) {
                $query->where('business_id', $businessId);
            }

            $stats = $query->selectRaw("
                COUNT(*) as total_transfers,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_transfers,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_transfers,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_transfers
            ")->first();

            return [
                'total_transfers' => $stats->total_transfers,
                'pending_transfers' => $stats->pending_transfers,
                'completed_transfers' => $stats->completed_transfers,
                'cancelled_transfers' => $stats->cancelled_transfers,
            ];
        });
    }

    /**
     * Get recall statistics
     */
    public function getRecallStatistics(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $cacheKey = "recall_stats:{$businessId}:{$dateFrom}:{$dateTo}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $dateFrom, $dateTo) {
            $query = RecallEvent::whereBetween('recall_date', [$dateFrom, $dateTo]);

            if ($businessId) {
                $query->where('business_id', $businessId);
            }

            $stats = $query->selectRaw("
                COUNT(*) as total_recalls,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_recalls,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_recalls,
                COALESCE(SUM(affected_quantity), 0) as affected_products
            ")->first();

            return [
                'total_recalls' => $stats->total_recalls,
                'active_recalls' => $stats->active_recalls,
                'completed_recalls' => $stats->completed_recalls,
                'affected_products' => $stats->affected_products,
            ];
        });
    }

    /**
     * Get loyalty statistics
     */
    public function getLoyaltyStatistics(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $cacheKey = "loyalty_stats:{$businessId}:{$dateFrom}:{$dateTo}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $dateFrom, $dateTo) {
            $query = LoyaltyTransaction::whereBetween('created_at', [$dateFrom, $dateTo]);

            if ($businessId) {
                $query->where('business_id', $businessId);
            }

            $stats = $query->selectRaw("
                COUNT(*) as total_transactions,
                SUM(CASE WHEN type = 'earned' THEN points ELSE 0 END) as points_earned,
                SUM(CASE WHEN type = 'redeemed' THEN points ELSE 0 END) as points_redeemed,
                SUM(CASE WHEN type = 'reward' THEN 1 ELSE 0 END) as total_rewards_issued
            ")->first();

            return [
                'total_transactions' => $stats->total_transactions,
                'points_earned' => $stats->points_earned,
                'points_redeemed' => $stats->points_redeemed,
                'total_rewards_issued' => $stats->total_rewards_issued,
            ];
        });
    }

    /**
     * Get receipt statistics
     */
    public function getReceiptStatistics(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $cacheKey = "receipt_stats:{$businessId}:{$dateFrom}:{$dateTo}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $dateFrom, $dateTo) {
            $query = Receipt::whereBetween('created_at', [$dateFrom, $dateTo]);

            if ($businessId) {
                $query->where('business_id', $businessId);
            }

            $stats = $query->selectRaw("
                COUNT(*) as total_receipts,
                SUM(CASE WHEN type = 'sale' THEN 1 ELSE 0 END) as sale_receipts,
                SUM(CASE WHEN type = 'purchase' THEN 1 ELSE 0 END) as purchase_receipts,
                SUM(CASE WHEN status = 'printed' THEN 1 ELSE 0 END) as printed_receipts
            ")->first();

            return [
                'total_receipts' => $stats->total_receipts,
                'sale_receipts' => $stats->sale_receipts,
                'purchase_receipts' => $stats->purchase_receipts,
                'printed_receipts' => $stats->printed_receipts,
            ];
        });
    }

    /**
     * Get comprehensive dashboard report
     */
    public function getComprehensiveReport(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $cacheKey = "comprehensive_report:{$businessId}";

        return $this->cacheService->remember($cacheKey, 300, function () use ($filters) {
            return [
                'overall' => $this->getOverallStatistics($filters),
                'sales_by_date' => $this->getSalesByDate($filters),
                'top_products' => $this->getTopSellingProducts($filters),
                'top_customers' => $this->getTopCustomers($filters),
                'profit_loss' => $this->getProfitAndLoss($filters),
                'warehouse' => $this->getWarehouseStatistics($filters['business_id'] ?? null),
                'transfers' => $this->getTransferStatistics($filters),
                'recalls' => $this->getRecallStatistics($filters),
                'loyalty' => $this->getLoyaltyStatistics($filters),
                'receipts' => $this->getReceiptStatistics($filters),
            ];
        });
    }

    /**
     * Invalidate cache for specific business
     */
    public function invalidateBusinessCache(int $businessId): void
    {
        $this->cacheService->invalidateBusiness($businessId);
    }

    /**
     * Invalidate all dashboard cache
     */
    public function invalidateDashboardCache(): void
    {
        $this->cacheService->invalidateTags(['dashboard', 'statistics', 'sales', 'purchases', 'inventory']);
    }
}
