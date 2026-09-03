<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $businessId = auth()->user()->business_id;

        // ── KPI Summary ──
        $totalCoupons = Coupon::where('business_id', $businessId)->count();
        $activeCoupons = Coupon::where('business_id', $businessId)
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })->count();

        $totalUsages = CouponUsage::whereHas('coupon', fn ($q) => $q->where('business_id', $businessId))->count();
        $totalDiscountGiven = CouponUsage::whereHas('coupon', fn ($q) => $q->where('business_id', $businessId))
            ->sum('discount_amount');

        // Revenue impact: orders used with coupons
        $couponRevenue = DB::table('coupon_usages')
            ->join('coupons', 'coupons.id', '=', 'coupon_usages.coupon_id')
            ->join('customer_orders', 'customer_orders.id', '=', 'coupon_usages.customer_order_id')
            ->where('coupons.business_id', $businessId)
            ->sum('customer_orders.total_amount');

        $avgOrderValue = DB::table('coupon_usages')
            ->join('customer_orders', 'customer_orders.id', '=', 'coupon_usages.customer_order_id')
            ->join('coupons', 'coupons.id', '=', 'coupon_usages.coupon_id')
            ->where('coupons.business_id', $businessId)
            ->avg('customer_orders.total_amount');

        $conversionRate = $totalCoupons > 0 ? round(($activeCoupons / max($totalCoupons, 1)) * 100, 1) : 0;

        // ── Top Performing Coupons ──
        $topCoupons = Coupon::where('business_id', $businessId)
            ->withCount(['usages' => function ($q) {
                $q->select(DB::raw('COUNT(*) as total_uses'));
            }])
            ->withSum('usages', 'discount_amount')
            ->orderByDesc('usages_count')
            ->limit(10)
            ->get();

        // ── Usage Over Time (last 30 days) ──
        $usageOverTime = CouponUsage::whereHas('coupon', fn ($q) => $q->where('business_id', $businessId))
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as uses'),
                DB::raw('SUM(discount_amount) as discount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Revenue with/without coupons ──
        $ordersWithCoupons = DB::table('coupon_usages')
            ->join('coupons', 'coupons.id', '=', 'coupon_usages.coupon_id')
            ->join('customer_orders', 'customer_orders.id', '=', 'coupon_usages.customer_order_id')
            ->where('coupons.business_id', $businessId)
            ->count();

        $totalOrders = DB::table('customer_orders')
            ->where('business_id', $businessId)
            ->count();

        // ── Type Distribution ──
        $typeDistribution = Coupon::where('business_id', $businessId)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type');

        return view('admin.coupons.analytics', compact(
            'totalCoupons', 'activeCoupons', 'totalUsages', 'totalDiscountGiven',
            'couponRevenue', 'avgOrderValue', 'conversionRate', 'topCoupons',
            'usageOverTime', 'ordersWithCoupons', 'totalOrders', 'typeDistribution',
        ));
    }
}
