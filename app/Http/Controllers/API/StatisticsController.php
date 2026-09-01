<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function summary(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $date = $request->input('date', today());

        $cacheKey = "statistics:summary:{$businessId}:{$date}";

        $data = $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $date) {
            // Use single query for all statistics
            $stats = Sale::where('business_id', $businessId)
                ->whereDate('created_at', $date)
                ->selectRaw('
                    COALESCE(SUM(totalAmount), 0) as sales,
                    COALESCE(SUM(lossProfit), 0) as profit_loss
                ')
                ->first();

            $purchase = Purchase::where('business_id', $businessId)
                ->whereDate('created_at', $date)
                ->sum('totalAmount');

            $income = Income::where('business_id', $businessId)
                ->whereDate('incomeDate', $date)
                ->sum('amount');

            $expense = Expense::where('business_id', $businessId)
                ->whereDate('expenseDate', $date)
                ->sum('amount');

            return [
                'sales' => $stats->sales,
                'purchase' => $purchase,
                'income' => (float) $income,
                'expense' => (float) $expense,
                'profit' => (float) ($stats->profit_loss + $income - $expense),
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function dashboard(Request $request)
    {
        $duration = $request->input('duration', 'weekly');
        $currentDate = Carbon::now();

        switch ($duration) {
            case 'weekly':
                $start = $currentDate->copy()->startOfWeek(Carbon::SATURDAY);
                $end = $currentDate->copy()->endOfWeek(Carbon::FRIDAY);
                $format = 'D';
                $period = $start->daysUntil($end);
                break;

            case 'monthly':
                $start = $currentDate->copy()->startOfMonth();
                $end = $currentDate->copy()->endOfMonth();
                $format = 'd';
                $period = $start->daysUntil($end);
                break;

            case 'yearly':
                $start = $currentDate->copy()->startOfYear();
                $end = $currentDate->copy()->endOfYear();
                $format = 'M';
                $period = $start->monthsUntil($end);
                break;

            default:
                return response()->json(['error' => 'Invalid duration'], 400);
        }

        $businessId = auth()->user()->business_id;
        $cacheKey = "dashboard:{$businessId}:{$duration}";

        $data = $this->cacheService->remember($cacheKey, 300, function () use ($businessId, $start, $end, $format, $period, $duration) {
            // Cache party stats
            $partyStats = $this->cacheService->rememberForBusiness($businessId, "party_stats:{$start}:{$end}", 300, function () use ($businessId, $start, $end) {
                return [
                    'total_customers' => Party::whereIn('type', ['Retailer', 'Wholesaler'])
                        ->where('business_id', $businessId)
                        ->whereBetween('created_at', [$start, $end])
                        ->count(),
                    'total_suppliers' => Party::whereIn('type', ['Supplier'])
                        ->where('business_id', $businessId)
                        ->whereBetween('created_at', [$start, $end])
                        ->count(),
                ];
            });

            // Cache stock stats
            $stockStats = $this->cacheService->rememberForBusiness($businessId, "stock_stats:{$start}:{$end}", 300, function () use ($businessId, $start, $end) {
                return [
                    'total_medicine' => (int) Stock::where('business_id', $businessId)
                        ->whereBetween('created_at', [$start, $end])
                        ->sum('productStock'),
                    'expired_medicine' => (int) Stock::where('business_id', $businessId)
                        ->where('expire_date', '<', today())
                        ->whereBetween('created_at', [$start, $end])
                        ->sum('productStock'),
                ];
            });

            // Get sales data with cache
            $salesData = $this->cacheService->rememberForBusiness($businessId, "sales_data:{$start}:{$end}", 300, function () use ($businessId, $start, $end) {
                return DB::table('sales')
                    ->select(DB::raw("DATE_FORMAT(saleDate, '%Y-%m-%d') as date"), DB::raw('SUM(totalAmount) as amount'))
                    ->where('business_id', $businessId)
                    ->whereBetween('saleDate', [$start, $end])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->keyBy('date');
            });

            // Get purchase data with cache
            $purchaseData = $this->cacheService->rememberForBusiness($businessId, "purchase_data:{$start}:{$end}", 300, function () use ($businessId, $start, $end) {
                return DB::table('purchases')
                    ->select(DB::raw("DATE_FORMAT(purchaseDate, '%Y-%m-%d') as date"), DB::raw('SUM(totalAmount) as amount'))
                    ->where('business_id', $businessId)
                    ->whereBetween('purchaseDate', [$start, $end])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->keyBy('date');
            });

            // Get loss data with cache
            $lossData = $this->cacheService->rememberForBusiness($businessId, "loss_data:{$start}:{$end}", 300, function () use ($businessId, $start, $end) {
                return DB::table('sales')
                    ->select(DB::raw("DATE_FORMAT(saleDate, '%Y-%m-%d') as date"), DB::raw('SUM(lossProfit) as amount'))
                    ->where('business_id', $businessId)
                    ->where('lossProfit', '<=', 0)
                    ->whereBetween('saleDate', [$start, $end])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->keyBy('date');
            });

            // Get profit data with cache
            $profitData = $this->cacheService->rememberForBusiness($businessId, "profit_data:{$start}:{$end}", 300, function () use ($businessId, $start, $end) {
                return DB::table('sales')
                    ->select(DB::raw("DATE_FORMAT(saleDate, '%Y-%m-%d') as date"), DB::raw('SUM(lossProfit) as amount'))
                    ->where('business_id', $businessId)
                    ->where('lossProfit', '>', 0)
                    ->whereBetween('saleDate', [$start, $end])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->keyBy('date');
            });

            return [
                'total_customers' => $partyStats['total_customers'],
                'total_suppliers' => $partyStats['total_suppliers'],
                'total_medicine' => $stockStats['total_medicine'],
                'expired_medicine' => $stockStats['expired_medicine'],

                'total_loss' => (float) array_sum($lossData->pluck('amount')->toArray()),
                'total_profit' => (float) array_sum($profitData->pluck('amount')->toArray()),
                'total_sales' => (float) array_sum($salesData->pluck('amount')->toArray()),
                'total_purchase' => (float) array_sum($purchaseData->pluck('amount')->toArray()),

                'sales' => $this->formatData($period, $salesData, $format, $duration),
                'purchases' => $this->formatData($period, $purchaseData, $format, $duration),

                'loss' => $this->formatData($period, $lossData, $format, $duration),
                'profit' => $this->formatData($period, $profitData, $format, $duration),
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    private function formatData($period, $datas, $format, $duration)
    {
        $rows = [];
        foreach ($period as $date) {
            if ($duration == 'yearly') {
                $key = $date->format($format);
                $dateKey = $date->format('Y-m');
                $amount = $datas->filter(function ($value, $key) use ($dateKey) {
                    return strpos($value->date, $dateKey) === 0;
                })->sum('amount');
            } else {
                $key = $date->format($format);
                $amount = $datas->get($date->format('Y-m-d'))?->amount ?? 0;
            }

            $rows[] = [
                'date' => $key,
                'amount' => $amount,
            ];
        }

        return $rows;
    }
}
