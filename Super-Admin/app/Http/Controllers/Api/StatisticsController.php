<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Category;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class StatisticsController extends Controller
{
    public function summary()
    {
        $business_id = auth()->user()->business_id;
        $date = request('date') ?? today();

        $sale_profit = Sale::where('business_id', $business_id)->whereDate('created_at', $date)->where('lossProfit', '>', 0)->sum('lossProfit');

        $data = [
            'sales' => (float)Sale::where('business_id', $business_id)->whereDate('created_at', $date)->sum('totalAmount'),
            'income' => (float)Income::where('business_id', $business_id)->whereDate('created_at', $date)->sum('amount') + $sale_profit,
            'expense' => (float)Expense::where('business_id', $business_id)->whereDate('created_at', $date)->sum('amount'),
            'purchase' => (float)Purchase::where('business_id', $business_id)->whereDate('created_at', $date)->sum('totalAmount'),
        ];

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function dashboard()
    {
        $currentDate = Carbon::now();
        $business_id = auth()->user()->business_id;
        $duration = request('duration');

        // Set date range, format, and period based on selected duration
        switch ($duration) {
            case 'today':
                $start = $currentDate->copy()->startOfDay();
                $end = $currentDate->copy()->endOfDay();
                $format = 'H';
                $period = $start->hoursUntil($end);
                break;

            case 'yesterday':
                $start = $currentDate->copy()->subDay()->startOfDay();
                $end = $currentDate->copy()->subDay()->endOfDay();
                $format = 'H';
                $period = $start->hoursUntil($end);
                break;

            case 'last_seven_days':
                $start = $currentDate->copy()->subDays(6)->startOfDay();
                $end = $currentDate->copy()->endOfDay();
                $format = 'd';
                $period = $start->daysUntil($end);
                break;

            case 'last_thirty_days':
                $start = $currentDate->copy()->subDays(29)->startOfDay();
                $end = $currentDate->copy()->endOfDay();
                $format = 'd';
                $period = $start->daysUntil($end);
                break;

            case 'current_month':
                $start = $currentDate->copy()->startOfMonth();
                $end = $currentDate->copy()->endOfMonth();
                $format = 'd';
                $period = $start->daysUntil($end);
                break;

            case 'last_month':
                $start = $currentDate->copy()->subMonthNoOverflow()->startOfMonth();
                $end = $currentDate->copy()->subMonthNoOverflow()->endOfMonth();
                $format = 'd';
                $period = $start->daysUntil($end);
                break;

            case 'current_year':
                $start = $currentDate->copy()->startOfYear();
                $end = $currentDate->copy()->endOfYear();
                $format = 'M';
                $period = $start->monthsUntil($end);
                break;

            case 'custom_date':
                if (request()->has('from_date') && request()->has('to_date')) {
                    $start = Carbon::parse(request('from_date'))->startOfDay();
                    $end = Carbon::parse(request('to_date'))->endOfDay();
                    $format = 'd';
                    $period = $start->daysUntil($end);
                } else {
                    return response()->json(['error' => 'From and To dates are required for custom date.'], 400);
                }
                break;

            default:
                return response()->json(['error' => 'Invalid duration'], 400);
        }

        // SQL date format for grouping
        $dateFormatSQL = match ($format) {
            'H' => '%H',
            'd' => '%Y-%m-%d',
            'M' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        // Sales data fetch and map
        $sales_data = Sale::selectRaw("DATE_FORMAT(created_at, '$dateFormatSQL') as date, SUM(totalAmount) as amount")
            ->where('business_id', $business_id)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->amount = (float) $item->amount;
                return $item;
            })
            ->keyBy('date');

        // Purchase data fetch and map
        $purchase_data = Purchase::selectRaw("DATE_FORMAT(created_at, '$dateFormatSQL') as date, SUM(totalAmount) as amount")
            ->where('business_id', $business_id)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->amount = (float) $item->amount;
                return $item;
            })
            ->keyBy('date');

        $income_amount = Income::where('business_id', $business_id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $sale_profit = Sale::where('business_id', $business_id)
            ->whereBetween('created_at', [$start, $end])
            ->where('lossProfit', '>', 0)
            ->sum('lossProfit');

        $expense_amount = Expense::where('business_id', $business_id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $productQuery = Product::select('id', 'business_id', 'productName', 'product_type', 'created_at')
            ->with(['stocks:id,business_id,product_id,productStock,productPurchasePrice', 'combo_products.stock'])
            ->where('business_id', $business_id);

        $total_stock_value = 0;

        $products = $productQuery->get()
            ->map(function ($item) {
                $item->source = 'product';
                return $item;
            });

        foreach ($products as $product) {
            // SINGLE / VARIANT
            if (in_array($product->product_type, ['single', 'variant'])) {
                foreach ($product->stocks as $stock) {
                    $total_stock_value += $stock->productStock * $stock->productPurchasePrice;
                }
            }

            // COMBO
            if ($product->product_type === 'combo') {
                foreach ($product->combo_products as $combo) {
                    $childStock = $combo->stock;
                    if ($childStock) {
                        $total_stock_value += ($childStock->productStock / $combo->quantity) * $combo->purchase_price;
                    }
                }
            }
        }

        $data = [
            'total_expense' => (float) $expense_amount,
            'total_income' => (float) $income_amount + $sale_profit,
            'total_items' => Product::where('business_id', $business_id)->count(),
            'total_categories' => Category::where('business_id', $business_id)->count(),
            'stock_value' => $total_stock_value,
            'total_due' => (float) Sale::where('business_id', $business_id)->whereBetween('saleDate', [$start, $end])->sum('dueAmount'),
            'total_profit' => (float) Sale::where('business_id', $business_id)->whereBetween('created_at', [$start, $end])->where('lossProfit', '>', 0)->sum('lossProfit'),
            'total_loss' => (float) Sale::where('business_id', $business_id)->whereBetween('created_at', [$start, $end])->where('lossProfit', '<', 0)->sum('lossProfit'),
            'sales' => $this->formatData($period, $sales_data, $format),
            'purchases' => $this->formatData($period, $purchase_data, $format),
        ];

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    private function formatData($period, $datas, $format)
    {
        $rows = [];

        foreach ($period as $date) {
            $key = $date->format($format);

            if ($format === 'M') {
                // Sum amounts for the month
                $dateKey = $date->format('Y-m');
                $amount = $datas->filter(fn($value) => strpos($value->date, $dateKey) === 0)->sum('amount');
            } elseif ($format === 'd') {
                // Get amount by full date
                $fullDateKey = $date->format('Y-m-d');
                $amount = $datas->get($fullDateKey)?->amount ?? 0;
            } elseif ($format === 'H') {
                // Get amount by hour
                $amount = $datas->get($key)?->amount ?? 0;
            } else {
                // Default: treat as full date
                $fullDateKey = $date->format('Y-m-d');
                $amount = $datas->get($fullDateKey)?->amount ?? 0;
            }

            $rows[] = [
                'date' => $key,
                'amount' => $amount,
            ];
        }

        return $rows;
    }
}
