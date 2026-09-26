<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FefoSetting;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $business_id = Auth::user()->business_id;
        $fefoEnabled = FefoSetting::getForBusiness($business_id)->fefo_enabled;

        $products_count = Product::where('business_id', $business_id)->count();
        $low_stock_count = DB::table('products')
            ->where('products.business_id', $business_id)
            ->join('stocks', 'products.id', '=', 'stocks.product_id')
            ->select('products.id', 'products.alert_qty', DB::raw('SUM(stocks.productStock) as totalStock'))
            ->groupBy('products.id', 'products.alert_qty')
            ->havingRaw('totalStock < alert_qty')
            ->count();

        $total_stock_value = DB::table('products')
            ->where('products.business_id', $business_id)
            ->join('stocks', 'products.id', '=', 'stocks.product_id')
            ->select(DB::raw('SUM(stocks.productStock * products.purchase_with_tax) as totalStockValue'))
            ->value('totalStockValue');

        $stocks = Product::select('id', 'productName', 'purchase_with_tax', 'sales_price')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->input('search').'%';
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('productName', 'like', $term)
                        ->orWhere('productCode', 'like', $term)
                        ->orWhereHas('stocks', function ($query) use ($term) {
                            $query->where('batch_no', 'like', $term);
                        });
                });
            })
            ->withSum('stocks', 'productStock')
            ->where('business_id', $business_id)
            ->with(['stocks' => function ($query) use ($fefoEnabled) {
                $query->select('id', 'batch_no', 'expire_date', 'product_id', 'productStock', 'created_at');
                if ($fefoEnabled) {
                    $query->orderByRaw('CASE WHEN expire_date IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('expire_date', 'asc')
                        ->orderBy('id', 'asc');
                }
            }])
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_products' => $products_count,
            'low_stock_count' => $low_stock_count,
            'total_stock_value' => $total_stock_value,
            'stocks' => $stocks,
        ]);
    }
}
