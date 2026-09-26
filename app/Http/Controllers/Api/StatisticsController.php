<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function summary(Request $request)
    {
        $business_id = auth()->user()->business_id;
        $date = $request->input('date', today());

        $total_income = Income::where('business_id', $business_id)->whereDate('incomeDate', $date)->sum('amount');
        $total_expense = Expense::where('business_id', $business_id)->whereDate('expenseDate', $date)->sum('amount');

        $sales = Sale::where('business_id', $business_id)->whereDate('created_at', $date)->sum('totalAmount');
        $purchase = Purchase::where('business_id', $business_id)->whereDate('created_at', $date)->sum('totalAmount');
        $profit = Sale::where('business_id', $business_id)->whereDate('created_at', $date)->sum('lossProfit') + $total_income - $total_expense;

        $data = [
            'sales' => $sales,
            'purchase' => $purchase,
            'income' => (float) $total_income,
            'expense' => (float) $total_expense,
            'profit' => (float) $profit,
        ];

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

        $business_id = auth()->user()->business_id;

        $cacheKey = "dashboard_data_{$business_id}_{$duration}";
        $data_cache = Cache::remember($cacheKey, 300, function () use ($business_id, $start, $end) {
            return [
                'total_customers' => Party::whereIn('type', ['Retailer', 'Wholesaler'])->where('business_id', $business_id)->whereBetween('created_at', [$start, $end])->count(),
                'total_suppliers' => Party::whereIn('type', ['Supplier'])->where('business_id', $business_id)->whereBetween('created_at', [$start, $end])->count(),
                'total_medicine' => (int) Stock::where('business_id', $business_id)->whereBetween('created_at', [$start, $end])->sum('productStock'),
                'expired_medicine' => (int) Stock::where('business_id', $business_id)->where('expire_date', '<', today())->whereBetween('created_at', [$start, $end])->sum('productStock'),
            ];
        });

        $sales_data = DB::table('sales')
            ->select(DB::raw("DATE_FORMAT(saleDate, '%Y-%m-%d') as date"), DB::raw('SUM(totalAmount) as amount'))
            ->where('business_id', $business_id)
            ->whereBetween('saleDate', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $purchase_data = DB::table('purchases')
            ->select(DB::raw("DATE_FORMAT(purchaseDate, '%Y-%m-%d') as date"), DB::raw('SUM(totalAmount) as amount'))
            ->where('business_id', $business_id)
            ->whereBetween('purchaseDate', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $loss_data = DB::table('sales')
            ->select(DB::raw("DATE_FORMAT(saleDate, '%Y-%m-%d') as date"), DB::raw('SUM(lossProfit) as amount'))
            ->where('business_id', $business_id)
            ->where('lossProfit', '<=', 0)
            ->whereBetween('saleDate', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $profit_data = DB::table('sales')
            ->select(DB::raw("DATE_FORMAT(saleDate, '%Y-%m-%d') as date"), DB::raw('SUM(lossProfit) as amount'))
            ->where('business_id', $business_id)
            ->where('lossProfit', '>', 0)
            ->whereBetween('saleDate', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $data = [
            'total_customers' => $data_cache['total_customers'],
            'total_suppliers' => $data_cache['total_suppliers'],
            'total_medicine' => $data_cache['total_medicine'],
            'expired_medicine' => $data_cache['expired_medicine'],

            'total_loss' => (float) array_sum($loss_data->pluck('amount')->toArray()),
            'total_profit' => (float) array_sum($profit_data->pluck('amount')->toArray()),
            'total_sales' => (float) array_sum($sales_data->pluck('amount')->toArray()),
            'total_purchase' => (float) array_sum($purchase_data->pluck('amount')->toArray()),

            'sales' => $this->formatData($period, $sales_data, $format, $duration),
            'purchases' => $this->formatData($period, $purchase_data, $format, $duration),

            'loss' => $this->formatData($period, $loss_data, $format, $duration),
            'profit' => $this->formatData($period, $profit_data, $format, $duration),
        ];

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
                $dateKey = $date->format('Y-m'); // For lookup purposes
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
