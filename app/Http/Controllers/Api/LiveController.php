<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * LiveController — small, cached aggregates powering the dashboard
 * "today in a line" strip (ap-live.js polls GET /api/v1/live/summary)
 * and any realtime surface. All queries are scoped to the authenticated
 * user's business and cached for 30 seconds per business.
 */
class LiveController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = "live.summary.{$businessId}";

        $data = Cache::remember($cacheKey, 30, function () use ($businessId) {
            $salesBase = Sale::where('business_id', $businessId)
                ->whereDate('created_at', Carbon::today());

            $salesCount = (clone $salesBase)->count();
            // Legacy POS rows keep total_amount = 0; revenue lives in the line
            // items (quantities * price), so derive it there.
            $salesTotal = DB::table('sale_details')
                ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->where('sales.business_id', $businessId)
                ->whereDate('sales.created_at', Carbon::today())
                ->sum(DB::raw('sale_details.quantities * sale_details.price'));
            $dueTotal = (clone $salesBase)->sum('due_amount');

            $lowStock = Stock::join('products', 'products.id', '=', 'stocks.product_id')
                ->where('stocks.business_id', $businessId)
                ->whereColumn('stocks.product_stock', '<=', 'products.alert_qty')
                ->distinct('stocks.product_id')
                ->count('stocks.product_id');

            $expiringSoon = Stock::where('stocks.business_id', $businessId)
                ->whereNotNull('expire_date')
                ->whereBetween('expire_date', [Carbon::today(), Carbon::today()->copy()->addDays(30)])
                ->count();

            return [
                'sales_count' => (int) $salesCount,
                'sales_total' => round((float) $salesTotal, 2),
                'due_total' => round((float) $dueTotal, 2),
                'low_stock' => (int) $lowStock,
                'expiring_soon' => (int) $expiringSoon,
                'generated_at' => now()->toIso8601String(),
            ];
        });

        return response()->json(['data' => $data]);
    }
}
