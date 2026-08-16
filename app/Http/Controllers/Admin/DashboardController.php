<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard-read')->only('index');
    }

    public function index()
    {
        $businesses = Business::with('enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name')->latest()->take(5)->get();

        return view('admin.dashboard.index', compact('businesses'));
    }

    public function designSystem()
    {
        // Dashboard KPI data
        $totalSales = \App\Models\Sale::where('business_id', auth()->user()->business_id ?? 1)->sum('totalAmount');
        $totalOrders = \App\Models\Sale::where('business_id', auth()->user()->business_id ?? 1)->count();
        $lowStockItems = \App\Models\Stock::where('productStock', '<=', 10)->count();
        $pendingApprovals = \App\Models\WorkflowInstance::where('status', 'pending')->count();

        return view('admin.dashboard.design-system', compact(
            'totalSales',
            'totalOrders',
            'lowStockItems',
            'pendingApprovals'
        ));
    }

    public function getDashboardData()
    {
        $data['total_businesses'] = Business::count();
        $data['expired_businesses'] = Business::where('will_expire', '<', now())->count();
        $data['plan_subscribes'] = PlanSubscribe::count();
        $data['business_categories'] = BusinessCategory::count();
        $data['total_plans'] = Plan::count();
        $data['total_staffs'] = User::whereNotIn('role', ['superadmin', 'staff', 'shop-owner'])->count();

        return response()->json($data);
    }

    public function subscriptionPlan(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $subscription = PlanSubscribe::with('plan:id,subscriptionName')
            ->select('plan_id', DB::raw('COUNT(*) as plan_count'))
            ->whereYear('created_at', $year)
            ->groupBy('plan_id')
            ->orderByDesc('plan_count')
            ->limit(4)
            ->get();

        return response()->json($subscription);
    }

    public function yearlySubscriptions(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $subscriptions = PlanSubscribe::whereYear('created_at', request('year') ?? date('Y'))
            ->selectRaw('MONTHNAME(created_at) as month, SUM(price) as total_amount')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->get();

        return response()->json($subscriptions);
    }
}
