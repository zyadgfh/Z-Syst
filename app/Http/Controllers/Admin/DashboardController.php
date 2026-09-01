<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->middleware('permission:dashboard-read')->only('index');
    }

    public function index()
    {
        $businesses = $this->cacheService->remember('admin:dashboard:recent_businesses', 300, function () {
            return Business::with('enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name')
                ->latest()
                ->take(5)
                ->get();
        });

        return view('admin.dashboard.index', compact('businesses'));
    }

    public function designSystem()
    {
        $businessId = auth()->user()->business_id ?? 1;

        // Cache KPI data for 5 minutes
        $kpis = $this->cacheService->remember("admin:dashboard:kpis:{$businessId}", 300, function () use ($businessId) {
            return [
                'totalSales' => \App\Models\Sale::where('business_id', $businessId)->sum('totalAmount'),
                'totalOrders' => \App\Models\Sale::where('business_id', $businessId)->count(),
                'lowStockItems' => \App\Models\Stock::where('productStock', '<=', 10)->count(),
                'pendingApprovals' => \App\Models\WorkflowInstance::where('status', 'pending')->count(),
            ];
        });

        return view('admin.dashboard.design-system', $kpis);
    }

    public function getDashboardData()
    {
        $data = $this->cacheService->remember('admin:dashboard:stats', 600, function () {
            return [
                'total_businesses' => Business::count(),
                'expired_businesses' => Business::where('will_expire', '<', now())->count(),
                'plan_subscribes' => PlanSubscribe::count(),
                'business_categories' => BusinessCategory::count(),
                'total_plans' => Plan::count(),
                'total_staffs' => User::whereNotIn('role', ['superadmin', 'staff', 'shop-owner'])->count(),
            ];
        });

        return response()->json($data);
    }

    public function subscriptionPlan(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $cacheKey = "admin:dashboard:subscription_plan:{$year}";
        $subscription = $this->cacheService->remember($cacheKey, 600, function () use ($year) {
            return PlanSubscribe::with('plan:id,subscriptionName')
                ->select('plan_id', DB::raw('COUNT(*) as plan_count'))
                ->whereYear('created_at', $year)
                ->groupBy('plan_id')
                ->orderByDesc('plan_count')
                ->limit(4)
                ->get();
        });

        return response()->json($subscription);
    }

    public function yearlySubscriptions(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $cacheKey = "admin:dashboard:yearly_subs:{$year}";
        $subscriptions = $this->cacheService->remember($cacheKey, 600, function () use ($year) {
            return PlanSubscribe::whereYear('created_at', $year)
                ->selectRaw('MONTHNAME(created_at) as month, SUM(price) as total_amount')
                ->groupBy('month')
                ->get();
        });

        return response()->json($subscriptions);
    }
}
