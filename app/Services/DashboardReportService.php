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
     * Get overall dashboard statistics
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
     * Get revenue statistics
     */
    protected function getRevenueStatistics(?int $businessId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $query = Sale::whereBetween('saleDate', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $sales = $query->get();

        return [
            'total_revenue' => $sales->sum('netTotal') ?? $sales->sum('totalAmount'),
            'total_sales' => $sales->count(),
            'average_order_value' => $sales->count() > 0 ? ($sales->sum('netTotal') ?? $sales->sum('totalAmount')) / $sales->count() : 0,
            'total_paid' => $sales->sum('paidAmount'),
            'total_due' => $sales->sum('dueAmount'),
        ];
    }

    /**
     * Get sales statistics
     */
    protected function getSalesStatistics(?int $businessId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $query = Sale::whereBetween('saleDate', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $sales = $query->get();

        return [
            'total_sales' => $sales->count(),
            'total_quantity' => $sales->sum('totalQuantity'),
            'total_discount' => $sales->sum('discount'),
            'sales_by_payment_type' => $sales->groupBy('paymentType')->map->count(),
        ];
    }

    /**
     * Get purchase statistics
     */
    protected function getPurchaseStatistics(?int $businessId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $query = Purchase::whereBetween('purchaseDate', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $purchases = $query->get();

        return [
            'total_purchases' => $purchases->count(),
            'total_purchase_amount' => $purchases->sum('netTotal') ?? $purchases->sum('totalAmount'),
            'total_paid' => $purchases->sum('paidAmount'),
            'total_due' => $purchases->sum('dueAmount'),
        ];
    }

    /**
     * Get inventory statistics
     */
    protected function getInventoryStatistics(?int $businessId): array
    {
        $query = Product::query();

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $products = $query->get();

        return [
            'total_products' => $products->count(),
            'total_stock' => $products->sum('stock'),
            'low_stock_products' => $products->where('stock', '<=', 10)->count(),
            'out_of_stock_products' => $products->where('stock', '<=', 0)->count(),
            'products_by_category' => $products->groupBy('category_id')->map->count(),
        ];
    }

    /**
     * Get customer statistics
     */
    protected function getCustomerStatistics(?int $businessId): array
    {
        $query = Party::where('type', 'customer');

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $customers = $query->get();

        return [
            'total_customers' => $customers->count(),
            'active_customers' => $customers->where('status', 1)->count(),
            'inactive_customers' => $customers->where('status', 0)->count(),
        ];
    }

    /**
     * Get subscription statistics
     */
    protected function getSubscriptionStatistics(Carbon $dateFrom, Carbon $dateTo): array
    {
        $subscriptions = PlanSubscribe::whereBetween('created_at', [$dateFrom, $dateTo])->get();

        return [
            'total_subscriptions' => $subscriptions->count(),
            'total_revenue' => $subscriptions->sum('price'),
            'active_subscriptions' => Business::where('will_expire', '>', now())->count(),
            'expired_subscriptions' => Business::where('will_expire', '<', now())->count(),
        ];
    }

    /**
     * Get sales by date chart data
     */
    public function getSalesByDate(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();
        $groupBy = $filters['group_by'] ?? 'day';

        $query = Sale::whereBetween('saleDate', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $sales = $query->get();

        $grouped = $sales->groupBy(function ($sale) use ($groupBy) {
            return match ($groupBy) {
                'hour' => $sale->saleDate->format('Y-m-d H:00'),
                'day' => $sale->saleDate->format('Y-m-d'),
                'week' => $sale->saleDate->startOfWeek()->format('Y-m-d'),
                'month' => $sale->saleDate->format('Y-m'),
                default => $sale->saleDate->format('Y-m-d'),
            };
        });

        return $grouped->map(function ($group) {
            return [
                'count' => $group->count(),
                'revenue' => $group->sum('netTotal') ?? $group->sum('totalAmount'),
            ];
        })->toArray();
    }

    /**
     * Get top selling products
     */
    public function getTopSellingProducts(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();
        $limit = $filters['limit'] ?? 10;

        $query = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(sale_details.quantity) as total_quantity'), DB::raw('SUM(sale_details.total) as total_revenue'))
            ->whereBetween('sales.saleDate', [$dateFrom, $dateTo])
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit);

        if ($businessId) {
            $query->where('sales.business_id', $businessId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get top customers
     */
    public function getTopCustomers(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();
        $limit = $filters['limit'] ?? 10;

        $query = Sale::whereBetween('saleDate', [$dateFrom, $dateTo])
            ->with('party')
            ->select('party_id', DB::raw('COUNT(*) as total_orders'), DB::raw('SUM(netTotal) as total_spent'));

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        return $query->groupBy('party_id')
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($sale) {
                return [
                    'customer_id' => $sale->party_id,
                    'customer_name' => $sale->party?->name ?? 'Unknown',
                    'total_orders' => $sale->total_orders,
                    'total_spent' => $sale->total_spent,
                ];
            })
            ->toArray();
    }

    /**
     * Get profit and loss report
     */
    public function getProfitAndLoss(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $salesQuery = Sale::whereBetween('saleDate', [$dateFrom, $dateTo]);
        $purchasesQuery = Purchase::whereBetween('purchaseDate', [$dateFrom, $dateTo]);

        if ($businessId) {
            $salesQuery->where('business_id', $businessId);
            $purchasesQuery->where('business_id', $businessId);
        }

        $totalSales = $salesQuery->sum('netTotal') ?? $salesQuery->sum('totalAmount');
        $totalPurchases = $purchasesQuery->sum('netTotal') ?? $purchasesQuery->sum('totalAmount');
        $grossProfit = $totalSales - $totalPurchases;

        return [
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'gross_profit' => $grossProfit,
            'profit_margin' => $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0,
        ];
    }

    /**
     * Get warehouse statistics
     */
    public function getWarehouseStatistics(?int $businessId): array
    {
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
    }

    /**
     * Get transfer statistics
     */
    public function getTransferStatistics(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $query = StockTransfer::whereBetween('transfer_date', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $transfers = $query->get();

        return [
            'total_transfers' => $transfers->count(),
            'pending_transfers' => $transfers->where('status', 'pending')->count(),
            'completed_transfers' => $transfers->where('status', 'completed')->count(),
            'cancelled_transfers' => $transfers->where('status', 'cancelled')->count(),
        ];
    }

    /**
     * Get recall statistics
     */
    public function getRecallStatistics(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $query = RecallEvent::whereBetween('recall_date', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $recalls = $query->get();

        return [
            'total_recalls' => $recalls->count(),
            'active_recalls' => $recalls->where('status', 'active')->count(),
            'completed_recalls' => $recalls->where('status', 'completed')->count(),
            'affected_products' => $recalls->sum('affected_quantity'),
        ];
    }

    /**
     * Get loyalty statistics
     */
    public function getLoyaltyStatistics(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $query = LoyaltyTransaction::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $transactions = $query->get();

        return [
            'total_transactions' => $transactions->count(),
            'points_earned' => $transactions->where('type', 'earned')->sum('points'),
            'points_redeemed' => $transactions->where('type', 'redeemed')->sum('points'),
            'total_rewards_issued' => $transactions->where('type', 'reward')->count(),
        ];
    }

    /**
     * Get receipt statistics
     */
    public function getReceiptStatistics(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth();

        $query = Receipt::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $receipts = $query->get();

        return [
            'total_receipts' => $receipts->count(),
            'sale_receipts' => $receipts->where('type', 'sale')->count(),
            'purchase_receipts' => $receipts->where('type', 'purchase')->count(),
            'printed_receipts' => $receipts->where('status', 'printed')->count(),
        ];
    }

    /**
     * Get comprehensive dashboard report
     */
    public function getComprehensiveReport(array $filters = []): array
    {
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
    }
}
