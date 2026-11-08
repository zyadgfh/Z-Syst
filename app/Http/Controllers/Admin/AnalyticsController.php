<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnomalyAlert;
use App\Models\Product;
use App\Models\SaleDetails;
use Illuminate\Http\Request;

/** ABC / dead stock / margins / anomaly feed. */
class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:analytics-read');
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->user()->business_id;

        return view('admin.analytics.index', [
            'abc' => $this->abc($businessId),
            'dead' => $this->deadStock($businessId),
            'margins' => $this->margins($businessId),
            'anomalies' => AnomalyAlert::forBusiness($businessId)->where('is_resolved', false)->latest('id')->limit(20)->get(),
        ]);
    }

    /** Pareto classification by 90-day revenue share. */
    protected function abc(int $businessId): array
    {
        $rows = SaleDetails::query()->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->join('products', 'products.id', '=', 'sale_details.product_id')
            ->where('sales.business_id', $businessId)
            ->where('sales.saleDate', '>=', now()->subDays(90)->toDateString())
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.id, products.name, SUM(sale_details.price * sale_details.quantities) revenue')
            ->orderByDesc('revenue')->get();

        $total = max(0.01, (float) $rows->sum('revenue'));
        $cum = 0;
        return $rows->map(function ($r) use (&$cum, $total) {
            $share = (float) $r->revenue / $total * 100;
            $cum += $share;
            $r->share = round($share, 2);
            $r->cumulative = round($cum, 2);
            $r->class = $cum <= 80 ? 'A' : ($cum <= 95 ? 'B' : 'C');
            return $r;
        })->take(50)->toArray();
    }

    protected function deadStock(int $businessId): array
    {
        return Product::where('business_id', $businessId)->with('stocks')->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'qty' => (int) $p->stocks->sum('productStock'),
                'value' => round((float) $p->stocks->sum(fn ($s) => $s->productStock * ($p->purchase_price ?? 0)), 2),
            ])
            ->filter(fn ($r) => $r['qty'] > 0)
            ->reject(function ($r) use ($businessId) {
                // has any sale in 60 days → not dead
                $name = $r['name'];
                return SaleDetails::whereHas('sale', fn ($q) => $q->where('business_id', $businessId)->where('saleDate', '>=', now()->subDays(60)))
                    ->whereHas('product', fn ($q) => $q->where('name', $name))->exists();
            })
            ->sortByDesc('value')->take(25)->values()->toArray();
    }

    protected function margins(int $businessId): array
    {
        return SaleDetails::query()->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->join('products', 'products.id', '=', 'sale_details.product_id')
            ->where('sales.business_id', $businessId)
            ->where('sales.saleDate', '>=', now()->subDays(30)->toDateString())
            ->groupBy('products.id', 'products.name')
            ->selectRaw("products.name, SUM(sale_details.quantities) qty, SUM(sale_details.lossProfit) profit, SUM(sale_details.price * sale_details.quantities) revenue")
            ->orderByDesc('profit')->limit(25)->get()
            ->map(function ($r) {
                $r->margin = $r->revenue > 0 ? round($r->profit / $r->revenue * 100, 1) : 0;
                return $r;
            })->toArray();
    }
}
