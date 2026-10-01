<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProductAnalyticsService
{
    public function analyze(int $businessId, array $filters = []): array
    {
        [$from, $to, $period] = $this->resolvePeriod($filters);
        $productId = !empty($filters['product_id']) ? (int) $filters['product_id'] : null;
        $warehouseId = !empty($filters['warehouse_id']) ? (int) $filters['warehouse_id'] : null;

        $cacheKey = sprintf(
            'analytics:product:%d:%s:%s:%s:%s',
            $businessId,
            $period,
            $from->toDateString(),
            $to->toDateString(),
            md5((string) json_encode([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ]))
        );

        return Cache::remember($cacheKey, 60, function () use (
            $businessId,
            $filters,
            $from,
            $to,
            $period,
            $productId,
            $warehouseId
        ) {
            return $this->buildAnalytics(
                $businessId,
                $filters,
                $from,
                $to,
                $period,
                $productId,
                $warehouseId
            );
        });
    }

    private function buildAnalytics(
        int $businessId,
        array $filters,
        Carbon $from,
        Carbon $to,
        string $period,
        ?int $productId,
        ?int $warehouseId
    ): array {
        $sales = $this->salesByDay($businessId, $from, $to, $productId);
        $salesByDay = $this->emptyBuckets($from, $to);

        foreach ($sales as $row) {
            $key = Carbon::parse($row->bucket_date)->format('Y-m-d');
            if (!isset($salesByDay[$key])) {
                continue;
            }

            $salesByDay[$key]['revenue'] = (float) $row->revenue;
            $salesByDay[$key]['orders'] = (int) $row->orders;
            $salesByDay[$key]['quantity'] = (float) $row->quantity;
        }

        $days = max(1, $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1);
        $revenue = (float) collect($salesByDay)->sum('revenue');
        $orders = (int) collect($salesByDay)->sum('orders');
        $soldQuantity = (float) collect($salesByDay)->sum('quantity');

        $previousFrom = $from->copy()->subDays($days)->startOfDay();
        $previousTo = $from->copy()->subDay()->endOfDay();
        $previous = $this->salesTotals($businessId, $previousFrom, $previousTo, $productId);

        $movementTotals = $this->movementTotals($businessId, $from, $to, $productId, $warehouseId);
        $aggregates = $this->periodAggregates($salesByDay, $from, $to, $period);

        return [
            'period' => [
                'type' => $period,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $days,
            ],
            'sales' => [
                'revenue' => round($revenue, 2),
                'orders' => $orders,
                'quantity' => round($soldQuantity, 2),
                'average_daily_revenue' => round($revenue / $days, 2),
                'average_daily_quantity' => round($soldQuantity / $days, 2),
                'average_order_value' => $orders ? round($revenue / $orders, 2) : 0,
                'averages' => [
                    'weekly_revenue' => round($aggregates['average_weekly_revenue'], 2),
                    'monthly_revenue' => round($aggregates['average_monthly_revenue'], 2),
                    'yearly_revenue' => round($aggregates['average_yearly_revenue'], 2),
                ],
                'comparison' => [
                    'previous_revenue' => round($previous['revenue'], 2),
                    'revenue_change_percent' => $this->change($previous['revenue'], $revenue),
                    'previous_orders' => $previous['orders'],
                    'orders_change_percent' => $this->change($previous['orders'], $orders),
                ],
                'daily' => array_values($salesByDay),
                'buckets' => $aggregates['buckets'],
            ],
            'movements' => array_map(
                fn ($value) => is_numeric($value) ? round($value, 2) : $value,
                $movementTotals
            ),
            'products' => $this->productBreakdown($businessId, $from, $to, $productId),
        ];
    }

    private function salesByDay(int $businessId, Carbon $from, Carbon $to, ?int $productId)
    {
        $query = DB::table('sales')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$from, $to]);

        if ($productId) {
            return $query
                ->join('sale_details', 'sale_details.sale_id', '=', 'sales.id')
                ->where('sale_details.product_id', $productId)
                ->selectRaw(
                    'DATE(sales.sale_date) AS bucket_date,
                     SUM(sale_details.quantities) AS quantity,
                     SUM(sale_details.quantities * sale_details.price) AS revenue,
                     COUNT(DISTINCT sales.id) AS orders'
                )
                ->groupByRaw('DATE(sales.sale_date)')
                ->orderBy('bucket_date')
                ->get()
                ->all();
        }

        $headers = $query
            ->selectRaw(
                'DATE(sales.sale_date) AS bucket_date,
                 SUM(sales.total_amount) AS revenue,
                 COUNT(*) AS orders'
            )
            ->groupByRaw('DATE(sales.sale_date)')
            ->orderBy('bucket_date')
            ->get()
            ->keyBy('bucket_date');

        $quantities = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->selectRaw(
                'DATE(sales.sale_date) AS bucket_date,
                 COALESCE(SUM(sale_details.quantities), 0) AS quantity'
            )
            ->groupByRaw('DATE(sales.sale_date)')
            ->get()
            ->keyBy('bucket_date');

        return $headers->map(function ($row, $key) use ($quantities) {
            $row->quantity = (float) ($quantities->get($key)->quantity ?? 0);
            return $row;
        })->values()->all();
    }

    private function salesTotals(int $businessId, Carbon $from, Carbon $to, ?int $productId): array
    {
        $query = DB::table('sales')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$from, $to]);

        if ($productId) {
            $row = $query
                ->join('sale_details', 'sale_details.sale_id', '=', 'sales.id')
                ->where('sale_details.product_id', $productId)
                ->selectRaw(
                    'COALESCE(SUM(sale_details.quantities * sale_details.price), 0) AS revenue,
                     COUNT(DISTINCT sales.id) AS orders'
                )
                ->first();
        } else {
            $row = $query
                ->selectRaw(
                    'COALESCE(SUM(sales.total_amount), 0) AS revenue,
                     COUNT(*) AS orders'
                )
                ->first();
        }

        return [
            'revenue' => (float) ($row->revenue ?? 0),
            'orders' => (int) ($row->orders ?? 0),
        ];
    }

    private function movementTotals(
        int $businessId,
        Carbon $from,
        Carbon $to,
        ?int $productId,
        ?int $warehouseId
    ): array {
        $query = DB::table('stock_movements')
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$from, $to]);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $rows = $query
            ->selectRaw('movement_type, SUM(quantity) AS quantity, COUNT(*) AS transactions')
            ->groupBy('movement_type')
            ->get();

        $totals = [
            'in' => 0,
            'out' => 0,
            'adjustment' => 0,
            'transfer' => 0,
            'return' => 0,
            'net' => 0,
            'transactions' => 0,
        ];

        foreach ($rows as $row) {
            $type = (string) $row->movement_type;
            $quantity = (float) $row->quantity;
            $transactions = (int) $row->transactions;

            if (array_key_exists($type, $totals)) {
                $totals[$type] += $quantity;
            }

            $totals['transactions'] += $transactions;
            $totals['net'] += match ($type) {
                'in', 'return' => $quantity,
                'out' => -$quantity,
                default => 0,
            };
        }

        return $totals;
    }

    private function productBreakdown(int $businessId, Carbon $from, Carbon $to, ?int $productId): array
    {
        $query = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->select(
                'products.id',
                'products.product_name',
                'products.product_code',
                DB::raw('SUM(sale_details.quantities) AS quantity'),
                DB::raw('SUM(sale_details.quantities * sale_details.price) AS revenue')
            )
            ->groupBy('products.id', 'products.product_name', 'products.product_code')
            ->orderByDesc('quantity')
            ->limit(50);

        if ($productId) {
            $query->where('products.id', $productId);
        }

        return $query->get()->map(fn ($row) => [
            'product_id' => (int) $row->id,
            'name' => $row->product_name,
            'code' => $row->product_code,
            'quantity' => round((float) $row->quantity, 2),
            'revenue' => round((float) $row->revenue, 2),
        ])->values()->all();
    }

    private function periodAggregates(array $daily, Carbon $from, Carbon $to, string $period): array
    {
        $buckets = [];

        foreach ($daily as $row) {
            $date = Carbon::parse($row['date']);

            $key = match ($period) {
                'year' => $date->format('Y-m'),
                default => $date->format('Y-m-d'),
            };

            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'period' => $key,
                    'revenue' => 0,
                    'orders' => 0,
                    'quantity' => 0,
                ];
            }

            $buckets[$key]['revenue'] += (float) $row['revenue'];
            $buckets[$key]['orders'] += (int) $row['orders'];
            $buckets[$key]['quantity'] += (float) $row['quantity'];
        }

        $weekCount = max(1, (int) ceil($from->copy()->startOfDay()->diffInDays($to->copy()->endOfDay()) / 7));
        $monthCount = max(1, $from->copy()->startOfMonth()->diffInMonths($to->copy()->startOfMonth()) + 1);
        $yearCount = max(1, $from->copy()->startOfYear()->diffInYears($to->copy()->startOfYear()) + 1);
        $revenue = (float) collect($daily)->sum('revenue');

        return [
            'buckets' => array_values($buckets),
            'average_weekly_revenue' => $revenue / $weekCount,
            'average_monthly_revenue' => $revenue / $monthCount,
            'average_yearly_revenue' => $revenue / $yearCount,
        ];
    }

    private function resolvePeriod(array $filters): array
    {
        $type = $filters['period'] ?? 'month';

        if ($type === 'custom') {
            $from = Carbon::parse($filters['date_from'] ?? now()->startOfMonth())->startOfDay();
            $to = Carbon::parse($filters['date_to'] ?? now())->endOfDay();
        } else {
            $date = Carbon::parse($filters['date'] ?? now());

            [$from, $to] = match ($type) {
                'week' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
                'year' => [$date->copy()->startOfYear(), $date->copy()->endOfYear()],
                default => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            };
        }

        return [$from, $to, $type];
    }

    private function emptyBuckets(Carbon $from, Carbon $to): array
    {
        $rows = [];

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $date) {
            $rows[$date->format('Y-m-d')] = [
                'date' => $date->format('Y-m-d'),
                'revenue' => 0,
                'orders' => 0,
                'quantity' => 0,
            ];
        }

        return $rows;
    }

    private function change(float $old, float $new): float
    {
        return $old == 0 ? ($new > 0 ? 100 : 0) : round((($new - $old) / $old) * 100, 2);
    }
}
