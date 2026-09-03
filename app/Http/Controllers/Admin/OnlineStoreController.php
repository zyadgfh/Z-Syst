<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnlineStoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard-read');
    }

    /**
     * Online store analytics dashboard
     */
    public function index(Request $request)
    {
        $period = $request->input('period', '30');
        $startDate = now()->subDays((int) $period);

        // KPIs
        $kpis = [
            'total_orders' => CustomerOrder::where('created_at', '>=', $startDate)->count(),
            'total_revenue' => CustomerOrder::where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
            'pending_orders' => CustomerOrder::where('status', 'pending')->count(),
            'average_order_value' => CustomerOrder::where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->avg('total_amount') ?? 0,
            'total_customers' => User::where('role', 'customer')->count(),
            'new_customers' => User::where('role', 'customer')
                ->where('created_at', '>=', $startDate)
                ->count(),
        ];

        // Recent orders
        $recentOrders = CustomerOrder::with('items.product')
            ->latest()
            ->take(10)
            ->get();

        // Top selling products
        $topProducts = CustomerOrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total_price) as total_revenue'))
            ->whereHas('order', function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
            })
            ->with('product:id,productName,images')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(10)
            ->get();

        // Orders by status (for chart)
        $ordersByStatus = CustomerOrder::where('created_at', '>=', $startDate)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Revenue by day (for chart)
        $revenueByDay = CustomerOrder::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('revenue', 'date');

        return view('admin.online-store.index', compact(
            'kpis',
            'recentOrders',
            'topProducts',
            'ordersByStatus',
            'revenueByDay',
            'period'
        ));
    }
}
