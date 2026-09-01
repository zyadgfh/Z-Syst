<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        $recentOrders = CustomerOrder::where('user_id', $user->id)
            ->with('items.product')
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'total_orders' => CustomerOrder::where('user_id', $user->id)->count(),
            'pending_orders' => CustomerOrder::where('user_id', $user->id)->where('status', 'pending')->count(),
            'delivered_orders' => CustomerOrder::where('user_id', $user->id)->where('status', 'delivered')->count(),
            'total_spent' => CustomerOrder::where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
        ];

        return view('customer.dashboard.index', compact('recentOrders', 'stats'));
    }

    public function orders(Request $request)
    {
        $user = auth()->user();
        $status = $request->input('status');

        $query = CustomerOrder::where('user_id', $user->id)
            ->with('items.product');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->latest()->paginate(10);

        return view('customer.dashboard.orders', compact('orders', 'status'));
    }

    public function orderDetail(string $orderNumber)
    {
        $user = auth()->user();

        $order = CustomerOrder::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with('items.product')
            ->firstOrFail();

        return view('customer.dashboard.order-detail', compact('order'));
    }
}
