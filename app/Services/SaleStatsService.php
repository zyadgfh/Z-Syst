<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

/**
 * SaleStatsService
 *
 * إحصائيات وتقارير المبيعات
 * Dashboard metrics, top products, top customers, revenue analysis
 */
class SaleStatsService
{
    /**
     * Get comprehensive dashboard statistics.
     */
    public function getDashboardStats(string $companyId, ?string $branchId = null): array
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();
        $yearStart = now()->startOfYear();

        $baseQuery = Sale::where('company_id', $companyId)
            ->where('status', '!=', 'voided')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        // Period queries
        $todayStats = (clone $baseQuery)->where('created_at', '>=', $today);
        $weekStats = (clone $baseQuery)->where('created_at', '>=', $weekStart);
        $monthStats = (clone $baseQuery)->where('created_at', '>=', $monthStart);
        $yearStats = (clone $baseQuery)->where('created_at', '>=', $yearStart);

        // Payment methods distribution
        $paymentMethods = (clone $baseQuery)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->where('created_at', '>=', $monthStart)
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->payment_method => [
                    'count' => (int) $item->count,
                    'total' => (float) $item->total,
                ],
            ]);

        return [
            'today' => [
                'count' => $todayStats->count(),
                'total' => (float) $todayStats->sum('total_amount'),
                'paid' => (float) $todayStats->sum('amount_paid'),
                'average' => (float) $todayStats->avg('total_amount') ?? 0,
            ],
            'this_week' => [
                'count' => $weekStats->count(),
                'total' => (float) $weekStats->sum('total_amount'),
                'paid' => (float) $weekStats->sum('amount_paid'),
                'average' => (float) $weekStats->avg('total_amount') ?? 0,
            ],
            'this_month' => [
                'count' => $monthStats->count(),
                'total' => (float) $monthStats->sum('total_amount'),
                'paid' => (float) $monthStats->sum('amount_paid'),
                'average' => (float) $monthStats->avg('total_amount') ?? 0,
                'payment_methods' => $paymentMethods,
            ],
            'this_year' => [
                'count' => $yearStats->count(),
                'total' => (float) $yearStats->sum('total_amount'),
                'paid' => (float) $yearStats->sum('amount_paid'),
            ],
            'all_time' => [
                'count' => (clone $baseQuery)->count(),
                'total' => (float) (clone $baseQuery)->sum('total_amount'),
                'average_order_value' => (float) (clone $baseQuery)->avg('total_amount') ?? 0,
            ],
        ];
    }

    /**
     * Get top selling products.
     */
    public function getTopProducts(string $companyId, ?string $branchId = null, int $limit = 10): array
    {
        $query = SaleItem::select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(line_total) as total_revenue'),
                DB::raw('COUNT(DISTINCT sale_id) as sale_count'),
            )
            ->whereHas('sale', function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId)
                    ->where('status', '!=', 'voided');
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        return $query->map(function ($item) {
            $product = $item->product;
            return [
                'product_id' => $item->product_id,
                'product_name' => $product?->name ?? $product?->product_name ?? 'منتج',
                'barcode' => $product?->barcode,
                'total_quantity' => (float) $item->total_quantity,
                'total_revenue' => (float) $item->total_revenue,
                'sale_count' => (int) $item->sale_count,
                'average_price_per_unit' => $item->total_quantity > 0
                    ? round((float) $item->total_revenue / (float) $item->total_quantity, 2)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Get top customers by sales volume.
     */
    public function getTopCustomers(string $companyId, ?string $branchId = null, int $limit = 10): array
    {
        $query = Sale::select(
                'customer_id',
                'customer_name',
                DB::raw('COUNT(*) as sale_count'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('AVG(total_amount) as average_order'),
                DB::raw('MAX(created_at) as last_purchase_date'),
            )
            ->where('company_id', $companyId)
            ->where('status', '!=', 'voided')
            ->whereNotNull('customer_name')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('customer_id', 'customer_name')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();

        return $query->map(function ($row) {
            return [
                'customer_id' => $row->customer_id,
                'customer_name' => $row->customer_name,
                'sale_count' => (int) $row->sale_count,
                'total_spent' => (float) $row->total_spent,
                'average_order' => (float) ($row->average_order ?? 0),
                'last_purchase_date' => $row->last_purchase_date?->toDateString(),
            ];
        })->toArray();
    }

    /**
     * Get daily revenue for a period (chart data).
     */
    public function getDailyRevenue(string $companyId, ?string $branchId = null, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        $query = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('SUM(amount_paid) as collected'),
            )
            ->where('company_id', $companyId)
            ->where('status', '!=', 'voided')
            ->where('created_at', '>=', $startDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill in missing dates with zero values
        $result = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dayData = $query->get($date);
            $result[] = [
                'date' => $date,
                'count' => (int) ($dayData->count ?? 0),
                'revenue' => (float) ($dayData->revenue ?? 0),
                'collected' => (float) ($dayData->collected ?? 0),
            ];
        }

        return $result;
    }
}

