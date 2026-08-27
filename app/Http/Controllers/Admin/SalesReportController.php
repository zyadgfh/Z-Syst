<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    /**
     * Sales report page with date range filters, summary stats, top products, and chart.
     */
    public function index(Request $request)
    {
        $businessId = auth()->user()->business_id;

        // Date range with defaults
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->input('date_to', now()->format('Y-m-d'));

        // Summary stats
        $summary = Sale::where('business_id', $businessId)
            ->whereDate('saleDate', '>=', $dateFrom)
            ->whereDate('saleDate', '<=', $dateTo)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(totalAmount), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(paidAmount), 0) as total_paid')
            ->selectRaw('COALESCE(SUM(dueAmount), 0) as total_due')
            ->selectRaw('COALESCE(AVG(totalAmount), 0) as avg_order_value')
            ->first();

        // Top products by revenue
        $topProducts = SaleDetails::whereHas('sale', function ($q) use ($businessId, $dateFrom, $dateTo) {
                $q->where('business_id', $businessId)
                  ->whereDate('saleDate', '>=', $dateFrom)
                  ->whereDate('saleDate', '<=', $dateTo);
            })
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->select('products.id', 'products.productName', 'products.images')
            ->selectRaw('SUM(sale_details.quantities) as total_qty')
            ->selectRaw('SUM(sale_details.price * sale_details.quantities) as total_revenue')
            ->groupBy('products.id', 'products.productName', 'products.images')
            ->orderByDesc('total_revenue')
            ->limit(15)
            ->get();

        // Revenue by day (for chart)
        $dailyRevenue = Sale::where('business_id', $businessId)
            ->whereDate('saleDate', '>=', $dateFrom)
            ->whereDate('saleDate', '<=', $dateTo)
            ->selectRaw('DATE(saleDate) as date')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(totalAmount), 0) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenue by payment type
        $byPaymentType = Sale::where('business_id', $businessId)
            ->whereDate('saleDate', '>=', $dateFrom)
            ->whereDate('saleDate', '<=', $dateTo)
            ->selectRaw('COALESCE(paymentType, "other") as payment_type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(totalAmount), 0) as total')
            ->groupBy('paymentType')
            ->orderByDesc('total')
            ->get();

        // Recent sales for the table
        $recentSales = Sale::where('business_id', $businessId)
            ->whereDate('saleDate', '>=', $dateFrom)
            ->whereDate('saleDate', '<=', $dateTo)
            ->with('party:id,name', 'user:id,name')
            ->latest('saleDate')
            ->limit(25)
            ->get();

        return view('admin.reports.sales', compact(
            'summary', 'topProducts', 'dailyRevenue',
            'byPaymentType', 'recentSales', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * AJAX: Get chart data for a given date range.
     */
    public function chartData(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->input('date_to', now()->format('Y-m-d'));

        $dailyRevenue = Sale::where('business_id', $businessId)
            ->whereDate('saleDate', '>=', $dateFrom)
            ->whereDate('saleDate', '<=', $dateTo)
            ->selectRaw('DATE(saleDate) as date')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(totalAmount), 0) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $dailyRevenue,
        ]);
    }
}
