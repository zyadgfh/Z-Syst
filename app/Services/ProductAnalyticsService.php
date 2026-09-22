<?php
namespace App\Services;

use App\Models\Sale;
use App\Models\StockMovement;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class ProductAnalyticsService
{
    public function analyze(int $businessId, array $filters = []): array
    {
        [$from, $to, $period] = $this->resolvePeriod($filters);
        $productId = !empty($filters['product_id']) ? (int) $filters['product_id'] : null;

        $salesQuery = Sale::where('business_id', $businessId)->whereBetween('saleDate', [$from, $to]);
        if ($productId) {
            $salesQuery->whereExists(function ($q) use ($productId) {
                $q->select(DB::raw(1))->from('sale_details')
                    ->whereColumn('sale_details.sale_id', 'sales.id')
                    ->where('sale_details.product_id', $productId);
            });
        }

        $sales = $salesQuery->select(['id','saleDate','netTotal','totalAmount','totalQuantity'])->get();
        $salesByDay = $this->emptyBuckets($from, $to);

        foreach ($sales as $sale) {
            $key = Carbon::parse($sale->saleDate)->format('Y-m-d');
            if (!isset($salesByDay[$key])) continue;
            $salesByDay[$key]['revenue'] += (float) ($sale->netTotal ?? $sale->totalAmount ?? 0);
            $salesByDay[$key]['orders']++;
            $salesByDay[$key]['quantity'] += (float) ($sale->totalQuantity ?? 0);
        }

        if ($productId) {
            $actual = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->where('sales.business_id', $businessId)
                ->where('sale_details.product_id', $productId)
                ->whereBetween('sales.saleDate', [$from, $to])
                ->selectRaw('SUM(sale_details.quantity) AS quantity, SUM(sale_details.total) AS revenue')
                ->first();
            $soldQuantity = (float) ($actual->quantity ?? 0);
        } else {
            $soldQuantity = (float) collect($salesByDay)->sum('quantity');
        }

        $movementQuery = StockMovement::where('business_id', $businessId)
            ->whereBetween('created_at', [$from, $to]);
        if ($productId) $movementQuery->where('product_id', $productId);

        $movementTotals = ['in'=>0,'out'=>0,'adjustment'=>0,'transfer'=>0,'return'=>0,'net'=>0,'transactions'=>0];
        $movements = $movementQuery->select(['movement_type','quantity','created_at'])->get();
        foreach ($movements as $movement) {
            $type = $movement->movement_type;
            $qty = (float) $movement->quantity;
            if (isset($movementTotals[$type])) $movementTotals[$type] += $qty;
            $movementTotals['net'] += match ($type) {
                'in','return' => $qty,
                'out' => -$qty,
                default => 0,
            };
        }
        $movementTotals['transactions'] = $movements->count();

        $days = max(1, $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1);
        $revenue = (float) collect($salesByDay)->sum('revenue');
        $orders = (int) collect($salesByDay)->sum('orders');

        $previousFrom = $from->copy()->subDays($days);
        $previousTo = $from->copy()->subDay();
        $previousRevenue = (float) Sale::where('business_id',$businessId)
            ->whereBetween('saleDate',[$previousFrom,$previousTo])
            ->get()
            ->sum(fn($s)=>(float)($s->netTotal ?? $s->totalAmount ?? 0));
        $previousOrders = Sale::where('business_id',$businessId)->whereBetween('saleDate',[$previousFrom,$previousTo])->count();

        return [
            'period'=>['type'=>$period,'from'=>$from->toDateString(),'to'=>$to->toDateString(),'days'=>$days],
            'sales'=>[
                'revenue'=>round($revenue,2),
                'orders'=>$orders,
                'quantity'=>round($soldQuantity,2),
                'average_daily_revenue'=>round($revenue/$days,2),
                'average_daily_quantity'=>round($soldQuantity/$days,2),
                'average_order_value'=>$orders ? round($revenue/$orders,2) : 0,
                'comparison'=>[
                    'previous_revenue'=>round($previousRevenue,2),
                    'revenue_change_percent'=>$this->change($previousRevenue,$revenue),
                    'previous_orders'=>$previousOrders,
                    'orders_change_percent'=>$this->change($previousOrders,$orders),
                ],
                'daily'=>array_values($salesByDay),
            ],
            'movements'=>array_map(fn($v)=>is_numeric($v)?round($v,2):$v,$movementTotals),
            'products'=>$this->productBreakdown($businessId,$from,$to,$productId),
        ];
    }

    private function productBreakdown(int $businessId, Carbon $from, Carbon $to, ?int $productId): array
    {
        $query = DB::table('sale_details')
            ->join('sales','sale_details.sale_id','=','sales.id')
            ->join('products','sale_details.product_id','=','products.id')
            ->where('sales.business_id',$businessId)
            ->whereBetween('sales.saleDate',[$from,$to])
            ->select('products.id','products.productName','products.productCode',
                DB::raw('SUM(sale_details.quantity) AS quantity'),
                DB::raw('SUM(sale_details.total) AS revenue'))
            ->groupBy('products.id','products.productName','products.productCode')
            ->orderByDesc('quantity')->limit(50);
        if ($productId) $query->where('products.id',$productId);

        return $query->get()->map(fn($r)=>[
            'product_id'=>(int)$r->id,'name'=>$r->productName,'code'=>$r->productCode,
            'quantity'=>round((float)$r->quantity,2),'revenue'=>round((float)$r->revenue,2),
        ])->values()->all();
    }

    private function resolvePeriod(array $filters): array
    {
        $type = $filters['period'] ?? 'month';
        if ($type === 'custom') {
            $from = Carbon::parse($filters['date_from'] ?? now()->startOfMonth())->startOfDay();
            $to = Carbon::parse($filters['date_to'] ?? now())->endOfDay();
        } else {
            $date = Carbon::parse($filters['date'] ?? now());
            [$from,$to] = match($type) {
                'week'=>[$date->copy()->startOfWeek(),$date->copy()->endOfWeek()],
                'year'=>[$date->copy()->startOfYear(),$date->copy()->endOfYear()],
                default=>[$date->copy()->startOfMonth(),$date->copy()->endOfMonth()],
            };
        }
        return [$from,$to,$type];
    }

    private function emptyBuckets(Carbon $from, Carbon $to): array
    {
        $rows=[];
        foreach(CarbonPeriod::create($from->copy()->startOfDay(),$to->copy()->startOfDay()) as $date)
            $rows[$date->format('Y-m-d')]=['date'=>$date->format('Y-m-d'),'revenue'=>0,'orders'=>0,'quantity'=>0];
        return $rows;
    }

    private function change(float $old,float $new): float
    {
        return $old == 0 ? ($new > 0 ? 100 : 0) : round((($new-$old)/$old)*100,2);
    }
}
