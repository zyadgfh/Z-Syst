<?php

namespace App\Http\Controllers\Api;

use App\Models\Vat;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PaymentType;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PlanSubscribe;
use App\Traits\DateFilterTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AcnooReportController extends Controller
{
    use DateFilterTrait;

    public function lossProfit(Request $request)
    {
        $user = auth()->user();
        $businessId = $user->business_id;

        $branchId = null;
        if (moduleCheck('MultiBranchAddon')) {
            $branchId = $user->branch_id ?? $user->active_branch_id;
        }

        $duration = $request->duration ?: 'today';


        $salesQuery = DB::table('sales')
            ->select(
                DB::raw('DATE(saleDate) as date'),
                DB::raw('SUM(actual_total_amount) as total_sales'),
                DB::raw('SUM(lossProfit) as total_sale_income')
            )
            ->where('business_id', $businessId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->groupBy(DB::raw('DATE(saleDate)'));


        $this->applyDateFilter($salesQuery, $duration, 'saleDate', $request->from_date, $request->to_date);


        $dailySales = $salesQuery->get();

        $sale_datas = $dailySales->map(fn($sale) => (object)[
            'type'          => 'Sale',
            'date'          => $sale->date,
            'total_sales'   => $sale->total_sales,
            'total_incomes' => $sale->total_sale_income,
        ]);

        $incomeQuery = DB::table('incomes')
            ->select(
                DB::raw('DATE(incomeDate) as date'),
                DB::raw('SUM(amount) as total_incomes')
            )
            ->where('business_id', $businessId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->groupBy(DB::raw('DATE(incomeDate)'));

        $this->applyDateFilter($incomeQuery, $duration, 'incomeDate', $request->from_date, $request->to_date);
        $dailyIncomes = $incomeQuery->get();

        $income_datas = $dailyIncomes->map(fn($income) => (object)[
            'type'          => 'Income',
            'date'          => $income->date,
            'total_incomes' => $income->total_incomes,
        ]);

        $mergedIncomeSaleData = collect();
        $allDates = $dailySales->pluck('date')
            ->merge($dailyIncomes->pluck('date'))
            ->unique()
            ->sort();

        foreach ($allDates as $date) {
            if ($income = $income_datas->firstWhere('date', $date)) {
                $mergedIncomeSaleData->push($income);
            }
            if ($sale = $sale_datas->firstWhere('date', $date)) {
                $mergedIncomeSaleData->push($sale);
            }
        }

        $dailyPayrolls = collect();

        if (moduleCheck('HrmAddon')) {
            $payrollQuery = DB::table('payrolls')
                ->select(
                    DB::raw('DATE(date) as date'),
                    DB::raw('SUM(amount) as total_payrolls')
                )
                ->where('business_id', $businessId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->groupBy(DB::raw('DATE(date)'));

            $this->applyDateFilter($payrollQuery, $duration, 'date', $request->from_date, $request->to_date);
            $dailyPayrolls = $payrollQuery->get();
        }

        $expenseQuery = DB::table('expenses')
            ->select(
                DB::raw('DATE(expenseDate) as date'),
                DB::raw('SUM(amount) as total_expenses_only')
            )
            ->where('business_id', $businessId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->groupBy(DB::raw('DATE(expenseDate)'));

        $this->applyDateFilter($expenseQuery, $duration, 'expenseDate', $request->from_date, $request->to_date);
        $dailyExpenses = $expenseQuery->get();

        $mergedExpenseData = collect();
        $allExpenseDates = $dailyExpenses->pluck('date')
            ->merge($dailyPayrolls->pluck('date'))
            ->unique()
            ->sort();

        foreach ($allExpenseDates as $date) {
            if ($expense = $dailyExpenses->firstWhere('date', $date)) {
                $mergedExpenseData->push((object)[
                    'type'           => 'Expense',
                    'date'           => $date,
                    'total_expenses' => $expense->total_expenses_only,
                ]);
            }

            if ($payroll = $dailyPayrolls->firstWhere('date', $date)) {
                $mergedExpenseData->push((object)[
                    'type'           => 'Payroll',
                    'date'           => $date,
                    'total_expenses' => $payroll->total_payrolls,
                ]);
            }
        }

        $grossSaleProfit   = $sale_datas->sum('total_sales');
        $grossIncomeProfit = $income_datas->sum('total_incomes') + $sale_datas->sum('total_incomes');
        $totalExpenses     = $mergedExpenseData->sum('total_expenses');
        $netProfit         = $grossIncomeProfit - $totalExpenses;

        $allTimeIncomes = DB::table('incomes')->where('business_id', $businessId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $allTimeSaleProfit = DB::table('sales')->where('business_id', $businessId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('lossProfit');

        $allTimePayrolls = moduleCheck('HrmAddon')
            ? DB::table('payrolls')->where('business_id', $businessId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('amount')
            : 0;

        $allTimeExpensesOnly = DB::table('expenses')->where('business_id', $businessId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $cardGrossProfit   = $allTimeIncomes + $allTimeSaleProfit;
        $totalCardExpenses = $allTimePayrolls + $allTimeExpensesOnly;
        $cardNetProfit     = $cardGrossProfit - $totalCardExpenses;

        return response()->json([
            'mergedIncomeSaleData' => $mergedIncomeSaleData->values(),
            'mergedExpenseData'    => $mergedExpenseData->values(),
            'grossSaleProfit'      => $grossSaleProfit,
            'grossIncomeProfit'    => $grossIncomeProfit,
            'totalExpenses'        => $totalExpenses,
            'netProfit'            => $netProfit,
            'cardGrossProfit'      => $cardGrossProfit,
            'totalCardExpenses'    => $totalCardExpenses,
            'cardNetProfit'        => $cardNetProfit,
        ]);
    }


    public function cashFlow(Request $request)
    {
        $query = Transaction::with([
            'paymentType:id,name',
            'sale:id,party_id',
            'sale.party:id,name',
            'saleReturn:id,sale_id',
            'purchase:id,party_id',
            'purchase.party:id,name',
            'purchaseReturn:id,purchase_id',
            'dueCollect:id,party_id',
            'dueCollect.party:id,name',
        ])
            ->where('business_id', auth()->user()->business_id)
            ->whereIn('type', ['debit', 'credit']);

        $total_cash_in = (clone $query)
            ->where('type', 'credit')
            ->sum('amount');

        $total_cash_out = (clone $query)
            ->where('type', 'debit')
            ->sum('amount');

        $total_running_cash = $total_cash_in - $total_cash_out;

        // Apply date filter
        $duration = $request->duration ?: 'today';
        $this->applyDateFilter($query, $duration, 'date', $request->from_date, $request->to_date);
        $cash_flows = $query->get();

        $firstDate = $cash_flows->first()?->date;

        if ($firstDate) {
            $opening_balance = (clone $query)
                ->whereDate('date', '<', $firstDate)
                ->selectRaw("SUM(CASE WHEN type='credit' THEN amount ELSE 0 END) - SUM(CASE WHEN type='debit' THEN amount ELSE 0 END) as balance")
                ->value('balance') ?? 0;
        } else {
            $opening_balance = 0;
        }

        return response()->json([
            'cash_in' => $total_cash_in,
            'cash_out' =>  $total_cash_out,
            'running_cash' =>  $total_running_cash,
            'initial_running_cash' =>  $opening_balance,
            'data' =>  $cash_flows,
        ]);
    }


    public function balanceSheetReport(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $duration   = $request->duration ?: 'today';
        $fromDate   = $request->from_date;
        $toDate     = $request->to_date;

        $productQuery = Product::select('id', 'business_id', 'productName', 'product_type', 'created_at')
            ->with(['stocks:id,business_id,product_id,productStock,productPurchasePrice', 'combo_products.stock'])
            ->where('business_id', $businessId);

        $this->applyDateFilter($productQuery, $duration, 'created_at', $fromDate, $toDate);

        $products = $productQuery->get()
            ->map(function ($item) {
                $item->source = 'product';
                return $item;
            });

        $bankQuery = PaymentType::where('business_id', $businessId);

        $this->applyDateFilter($bankQuery, $duration, 'opening_date', $fromDate, $toDate);

        $banks = $bankQuery->get()
            ->map(function ($item) {
                $item->source = 'bank';
                return $item;
            });

        $product_bank_datas = $products->merge($banks);

        $total_stock_value = 0;

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

        $totalBankBalance = $banks->sum('balance');
        $total_asset      = $total_stock_value + $totalBankBalance;

        return response()->json([
            'asset_datas' => $product_bank_datas,
            'total_asset' => $total_asset,
        ]);
    }


    public function subscriptionReport(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $duration   = $request->duration ?: 'today';
        $fromDate   = $request->from_date;
        $toDate     = $request->to_date;

        $subscriptionQuery = PlanSubscribe::with([
            'plan:id,subscriptionName',
            'business:id,companyName,business_category_id,pictureUrl',
            'business.category:id,name',
            'gateway:id,name'
        ])->where('business_id', $businessId);

        $this->applyDateFilter($subscriptionQuery, $duration, 'created_at', $fromDate, $toDate);

        $subscriptions = $subscriptionQuery->get();

        return response()->json([
            'data' => $subscriptions,
        ]);
    }

    public function taxReport(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $duration   = $request->duration ?: 'today';
        $fromDate   = $request->from_date;
        $toDate     = $request->to_date;

        $vats = Vat::where('business_id', $businessId)->whereStatus(1)->get();

        //sales
        $salesQuery = Sale::with('party:id,name,email,phone,type', 'vat:id,name')
            ->where('business_id', $businessId)
            ->where('vat_amount', '>', 0);

        $this->applyDateFilter($salesQuery, $duration, 'created_at', $fromDate, $toDate);

        $sales = $salesQuery->get()->map(function ($item) {
            $item->source = 'sale'; // append a source field
            return $item;
        });

        $salesTotalAmount = $sales->sum('totalAmount');
        $salesTotalDiscount = $sales->sum('discountAmount');
        $salesTotalVat = $sales->sum('vat_amount');

        $salesVatTotals = [];
        foreach ($vats as $vat) {
            $salesVatTotals[$vat->id] = $sales->where('vat_id', $vat->id)->sum('vat_amount');
        }

        //purchase
        $purchaseQuery = Purchase::with('party:id,name,email,phone,type', 'vat:id,name')
            ->where('business_id', $businessId)
            ->where('vat_amount', '>', 0);

        $this->applyDateFilter($purchaseQuery, $duration, 'created_at', $fromDate, $toDate);

        $purchases = $purchaseQuery->get()->map(function ($item) {
            $item->source = 'purchase'; // append a source field
            return $item;
        });

        $purchasesTotalAmount = $purchases->sum('totalAmount');
        $purchasesTotalDiscount = $purchases->sum('discountAmount');
        $purchasesTotalVat = $purchases->sum('vat_amount');

        $purchasesVatTotals = [];
        foreach ($vats as $vat) {
            $purchasesVatTotals[$vat->id] = $purchases->where('vat_id', $vat->id)->sum('vat_amount');
        }

        return response()->json([
            'sales' => $sales,
            'sales_total_amount' => $salesTotalAmount,
            'sales_total_discount' => $salesTotalDiscount,
            'sales_total_vat' => $salesTotalVat,
            'purchases' => $purchases,
            'purchases_total_amount' => $purchasesTotalAmount,
            'purchases_total_discount' => $purchasesTotalDiscount,
            'purchases_total_vat' => $purchasesTotalVat,
        ]);
    }


    public function billWiseProfitReport(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $duration   = $request->duration ?: 'today';
        $fromDate   = $request->from_date;
        $toDate     = $request->to_date;

        $billQuery = Sale::select('id', 'business_id', 'party_id', 'invoiceNumber', 'saleDate', 'totalAmount', 'lossProfit')
            ->with('party:id,name', 'details:id,sale_id,product_id,price,quantities,productPurchasePrice,lossProfit', 'details.product:id,productName')->where('business_id', $businessId);

        $this->applyDateFilter($billQuery, $duration, 'saleDate', $fromDate, $toDate);
        $bills  = $billQuery->get();

        $total_amount = $bills->sum('totalAmount');
        $total_bill_profit = $bills
            ->where('lossProfit', '>=', 0)
            ->sum('lossProfit');

        $total_bill_loss = $bills
            ->where('lossProfit', '<', 0)
            ->sum('lossProfit');

        return response()->json([
            'data' => $bills,
            'total_amount'  => $total_amount,
            'total_bill_profit'  => $total_bill_profit,
            'total_bill_loss'    => $total_bill_loss,
        ]);
    }

    public function productSaleHistory(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $duration   = $request->duration ?: 'today';

        $productQuery = Product::with(['saleDetails', 'purchaseDetails', 'saleDetails.sale', 'stocks', 'combo_products'])
            ->where('business_id', $businessId)
            ->whereHas('saleDetails.sale', function ($sale) use ($duration, $request) {
                $this->applyDateFilter($sale, $duration, 'saleDate', $request->from_date, $request->to_date);
            });

        $products = $productQuery->get();

        $total_purchase_qty = $products->sum(function ($product) {
            return $product->purchaseDetails->sum('quantities');
        });
        $total_sale_qty = $products->sum(function ($product) {
            return $product->saleDetails->sum('quantities');
        });

        return response()->json([
            'data' => $products,
            'total_purchase_qty' => $total_purchase_qty,
            'total_sale_qty' => $total_sale_qty,
        ]);
    }


    public function productSaleHistoryDetails(Request $request, $productId)
    {
        $businessId = auth()->user()->business_id;
        $duration   = $request->duration ?: 'today';
        $fromDate   = $request->from_date;
        $toDate     = $request->to_date;

        $product = Product::select('id', 'business_id', 'productName')
            ->with([
                'saleDetails' => function ($q) use ($duration, $fromDate, $toDate) {

                    $q->whereHas('sale', function ($sale) use ($duration, $fromDate, $toDate) {
                        $this->applyDateFilter($sale, $duration, 'saleDate', $fromDate, $toDate);
                    });

                    $q->select('id', 'sale_id', 'product_id', 'quantities', 'price', 'productPurchasePrice')
                        ->with([
                            'sale:id,invoiceNumber,saleDate'
                        ]);
                },
            ])->where('business_id', $businessId)->findOrFail($productId);

        $totalQuantities = $product->saleDetails->sum('quantities');
        $totalSalePrice = $product->saleDetails->sum('price');
        $totalPurchasePrice = $product->saleDetails->sum('productPurchasePrice');

        return response()->json([
            'data' => $product,
            'total_quantities'        => $totalQuantities,
            'total_sale_price'        => $totalSalePrice,
            'total_purchase_price'    => $totalPurchasePrice,
        ]);
    }

    public function productPurchaseHistory(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $duration   = $request->duration ?: 'today';

        $productQuery = Product::with(['saleDetails', 'purchaseDetails', 'purchaseDetails.purchase', 'stocks', 'combo_products'])
            ->where('business_id', $businessId)
            ->whereHas('purchaseDetails.purchase', function ($purchase) use ($duration, $request) {
                $this->applyDateFilter($purchase, $duration, 'purchaseDate', $request->from_date, $request->to_date);
            });

        $products = $productQuery->get();

        $total_purchase_qty = $products->sum(function ($product) {
            return $product->purchaseDetails->sum('quantities');
        });
        $total_sale_qty = $products->sum(function ($product) {
            return $product->saleDetails->sum('quantities');
        });

        return response()->json([
            'data' => $products,
            'total_purchase_qty' => $total_purchase_qty,
            'total_sale_qty' => $total_sale_qty,
        ]);
    }

    public function productPurchaseHistoryDetails(Request $request, $productId)
    {
        $businessId = auth()->user()->business_id;
        $duration   = $request->duration ?: 'today';
        $fromDate   = $request->from_date;
        $toDate     = $request->to_date;

        $product = Product::select('id', 'business_id', 'productName')
            ->with([
                'purchaseDetails' => function ($q) use ($duration, $fromDate, $toDate) {

                    $q->whereHas('purchase', function ($purchase) use ($duration, $fromDate, $toDate) {
                        $this->applyDateFilter($purchase, $duration, 'purchaseDate', $fromDate, $toDate);
                    });

                    $q->select('id', 'purchase_id', 'product_id', 'quantities', 'productPurchasePrice')
                        ->with([
                            'purchase:id,invoiceNumber,purchaseDate'
                        ]);
                },
            ])->where('business_id', $businessId)->findOrFail($productId);

        $totalQuantities = $product->purchaseDetails->sum('quantities');
        $totalPurchasePrice = $product->purchaseDetails->sum('productPurchasePrice');

        return response()->json([
            'data' => $product,
            'total_quantities'        => $totalQuantities,
            'total_purchase_price'    => $totalPurchasePrice,
        ]);
    }
}
