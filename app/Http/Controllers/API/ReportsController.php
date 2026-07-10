<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\DueCollect;
use App\Models\SaleReturn;
use App\Models\PurchaseReturn;
use App\Models\SaleReturnDetails;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\PurchaseReturnDetail;

class ReportsController extends Controller
{
    public function purchaseReport()
    {
        $query = Purchase::select('id', 'party_id', 'invoiceNumber', 'purchaseDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType', 'note')
                    ->with('party:id,name,phone')
                    ->withCount('purchaseReturns')
                    ->when(request('search'), function ($query) {
                        $query->where(function ($subQuery) {
                            $subQuery->where('paymentType', 'like', '%' . request('search') . '%')
                                ->orWhere('invoiceNumber', 'like', '%' . request('search') . '%')
                                ->orWhere('note', 'like', '%' . request('search') . '%')
                                ->orWhereHas('party', function ($query) {
                                    $query->where('name', 'like', '%' . request('search') . '%')
                                        ->orWhere('phone', 'like', '%' . request('search') . '%');
                                });
                        });
                    })
                    ->when(request('from_date') || request('to_date'), function ($query) {
                        $query->whereBetween('purchaseDate', [request('from_date'), request('to_date')]);
                    })
                    ->when(request('payment_status') == 'paid', function ($query) {
                        $query->where('dueAmount', '<=', 0);
                    })
                    ->when(request('payment_status') == 'unpaid', function ($query) {
                        $query->where('dueAmount', '>', 0);
                    })
                    ->when(request('party_id'), function ($query) {
                        $query->where('party_id', request('party_id'));
                    })
                    ->where('business_id', auth()->user()->business_id);

        $data = (clone $query)->latest()->paginate(10);
        $total_paid = (clone $query)->sum('paidAmount');
        $total_due = (clone $query)->sum('dueAmount');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_paid' => $total_paid,
            'total_due' => $total_due,
            'data' => $data,
        ]);
    }

    public function salesReport()
    {
        $query = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType')
                ->with('party:id,name,phone')
                ->withCount('saleReturns')
                ->when(request('search'), function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->where('paymentType', 'like', '%' . request('search') . '%')
                            ->orWhere('invoiceNumber', 'like', '%' . request('search') . '%')
                            ->orWhere('meta', 'like', '%' . request('search') . '%')
                            ->orWhereHas('party', function ($query) {
                                $query->where('name', 'like', '%' . request('search') . '%')
                                    ->orWhere('phone', 'like', '%' . request('search') . '%');
                            });
                    });
                })
                ->when(request('from_date') || request('to_date'), function ($query) {
                    $query->whereBetween('saleDate', [request('from_date'), request('to_date')]);
                })
                ->when(request('payment_status') == 'paid', function ($query) {
                    $query->where('dueAmount', '<=', 0);
                })
                ->when(request('payment_status') == 'unpaid', function ($query) {
                    $query->where('dueAmount', '>', 0);
                })
                ->when(request('party_id'), function ($query) {
                    $query->where('party_id', request('party_id'));
                })
                ->where('business_id', auth()->user()->business_id);

        $data = (clone $query)->latest()->paginate(10);
        $total_paid = (clone $query)->sum('paidAmount');
        $total_due = (clone $query)->sum('dueAmount');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_paid' => $total_paid,
            'total_due' => $total_due,
            'data' => $data,
        ]);
    }

    public function dueCollectsReport()
    {
        $query = DueCollect::select('id', 'party_id', 'invoiceNumber', 'totalDue', 'dueAmountAfterPay', 'payDueAmount', 'paymentType', 'paymentDate')
                    ->with('party:id,name,phone')
                    ->when(request('search'), function ($query) {
                        $query->where(function ($subQuery) {
                            $subQuery->where('paymentType', 'like', '%' . request('search') . '%')
                                ->orWhere('invoiceNumber', 'like', '%' . request('search') . '%')
                                ->orWhereHas('party', function ($query) {
                                    $query->where('name', 'like', '%' . request('search') . '%')
                                        ->orWhere('phone', 'like', '%' . request('search') . '%');
                                });
                        });
                    })
                    ->when(request('from_date') || request('to_date'), function ($query) {
                        $query->whereBetween('paymentDate', [request('from_date'), request('to_date')]);
                    })
                    ->where('business_id', auth()->user()->business_id);

        $data = (clone $query)->latest()->paginate(10);
        $total_paid = (clone $query)->sum('payDueAmount');
        $total_due = (clone $query)->sum('totalDue');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_paid' => $total_paid,
            'total_due' => $total_due,
            'data' => $data,
        ]);
    }

    public function lossProfitReport()
    {
        $query = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'dueAmount', 'lossProfit', 'totalAmount')
                    ->where('business_id', auth()->user()->business_id)
                    ->with('party:id,name,phone')
                    ->when(request('search'), function ($query) {
                        $query->where(function ($subQuery) {
                            $subQuery->where('invoiceNumber', 'like', '%' . request('search') . '%')
                                ->orWhere('dueAmount', 'like', '%' . request('search') . '%')
                                ->orWhere('meta', 'like', '%' . request('search') . '%')
                                ->orWhereHas('party', function ($query) {
                                    $query->where('name', 'like', '%' . request('search') . '%')
                                        ->orWhere('phone', 'like', '%' . request('search') . '%');
                                });
                        });
                    })
                    ->when(request('from_date') || request('to_date'), function ($query) {
                        $query->whereBetween('saleDate', [request('from_date'), request('to_date')]);
                    })
                    ->when(request('payment_status') == 'paid', function ($query) {
                        $query->where('dueAmount', '<=', 0);
                    })
                    ->when(request('payment_status') == 'unpaid', function ($query) {
                        $query->where('dueAmount', '>', 0);
                    });

        $data = (clone $query)->latest()->paginate(10);
        $total_profit = (clone $query)->where('lossProfit', '>', 0)->sum('lossProfit');
        $total_loss = (clone $query)->where('lossProfit', '<=', 0)->sum('lossProfit');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_profit' => $total_profit,
            'total_loss' => $total_loss,
            'data' => $data,
        ]);
    }

    public function incomeReport()
    {
        $query = Income::with('category:id,categoryName')
                    ->when(request('search'), function ($query) {
                        $query->where(function ($subQuery) {
                            $subQuery->where('amount', 'like', '%' . request('search') . '%')
                                ->orWhere('incomeFor', 'like', '%' . request('search') . '%')
                                ->orWhere('paymentType', 'like', '%' . request('search') . '%')
                                ->orWhere('referenceNo', 'like', '%' . request('search') . '%')
                                ->orWhere('note', 'like', '%' . request('search') . '%')
                                ->orWhereHas('category', function ($query) {
                                    $query->where('categoryName', 'like', '%' . request('search') . '%');
                                });
                        });
                    })
                    ->when(request('from_date') || request('to_date'), function ($query) {
                        $query->whereBetween('incomeDate', [request('from_date'), request('to_date')]);
                    })
                    ->where('business_id', auth()->user()->business_id);

        $data = (clone $query)->latest()->paginate(10);
        $total_income = (clone $query)->sum('amount');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_income' => $total_income,
            'data' => $data,
        ]);
    }

    public function expenseReport()
    {
        $query = Expense::with('category:id,categoryName')
                    ->when(request('search'), function ($query) {
                        $query->where(function ($subQuery) {
                            $subQuery->where('amount', 'like', '%' . request('search') . '%')
                                ->orWhere('expanseFor', 'like', '%' . request('search') . '%')
                                ->orWhere('paymentType', 'like', '%' . request('search') . '%')
                                ->orWhere('referenceNo', 'like', '%' . request('search') . '%')
                                ->orWhere('note', 'like', '%' . request('search') . '%')
                                ->orWhereHas('category', function ($query) {
                                    $query->where('categoryName', 'like', '%' . request('search') . '%');
                                });
                        });
                    })
                    ->when(request('from_date') || request('to_date'), function ($query) {
                        $query->whereBetween('expenseDate', [request('from_date'), request('to_date')]);
                    })
                    ->where('business_id', auth()->user()->business_id);

        $data = (clone $query)->latest()->paginate(10);
        $total_expense = (clone $query)->sum('amount');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_expense' => $total_expense,
            'data' => $data,
        ]);
    }

    public function lowStockReport()
    {
        $query = DB::table('products')
                    ->where('products.business_id', auth()->user()->business_id)
                    ->join('stocks', 'products.id', '=', 'stocks.product_id')
                    ->select('products.id', 'products.productName', 'products.productCode', 'products.alert_qty', 'products.sales_price', DB::raw('SUM(stocks.productStock) as totalStock'))
                    ->groupBy('products.id', 'products.productName', 'products.productCode', 'products.alert_qty', 'products.sales_price')
                    ->havingRaw('totalStock < alert_qty')
                    ->when(request('search'), function ($query) {
                        $query->where('productName', 'like', '%' . request('search') . '%')
                            ->orWhere('productCode', 'like', '%' . request('search') . '%');
                    });

        $data = (clone $query)->paginate(10);
        $total_item = (clone $query)->count();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_expense' => $total_item,
            'data' => $data,
        ]);
    }

    public function taxesReport()
    {
        $query = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType')
                ->with('party:id,name,phone')
                ->when(request('search'), function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->where('paymentType', 'like', '%' . request('search') . '%')
                            ->orWhere('invoiceNumber', 'like', '%' . request('search') . '%')
                            ->orWhere('meta', 'like', '%' . request('search') . '%')
                            ->orWhereHas('party', function ($query) {
                                $query->where('name', 'like', '%' . request('search') . '%')
                                    ->orWhere('phone', 'like', '%' . request('search') . '%');
                            });
                    });
                })
                ->when(request('from_date') || request('to_date'), function ($query) {
                    $query->whereBetween('saleDate', [request('from_date'), request('to_date')]);
                })
                ->when(request('payment_status') == 'paid', function ($query) {
                    $query->where('dueAmount', '<=', 0);
                })
                ->when(request('payment_status') == 'unpaid', function ($query) {
                    $query->where('dueAmount', '>', 0);
                })
                ->when(request('party_id'), function ($query) {
                    $query->where('party_id', request('party_id'));
                })
                ->where('business_id', auth()->user()->business_id);

        $data = (clone $query)->latest()->paginate(10);
        $total_paid = (clone $query)->sum('paidAmount');
        $total_due = (clone $query)->sum('dueAmount');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_paid' => $total_paid,
            'total_due' => $total_due,
            'data' => $data,
        ]);
    }

    public function saleReturnReport()
    {
        $baseQuery = SaleReturn::where('business_id', auth()->user()->business_id);

        $default_total_return = SaleReturnDetails::whereIn('sale_return_id', $baseQuery->pluck('id'))->sum('return_amount');
        $default_total_qty = SaleReturnDetails::whereIn('sale_return_id', $baseQuery->pluck('id'))->sum('return_qty');

        $filteredQuery = $baseQuery->select('id', 'sale_id', 'return_date', 'invoice_no')
                            ->with([
                                'details:id,sale_return_id,return_qty,return_amount',
                                'sale:id,party_id,invoiceNumber,totalAmount',
                                'sale.party:id,name',
                            ])
                            ->when(request('search'), function ($query) {
                                $search = request('search');
                                $query->where('invoice_no', 'like', "%$search%")
                                    ->orWhereHas('sale.party', function ($subQuery) use ($search) {
                                        $subQuery->where('name', 'like', "%$search%")
                                            ->orWhere('phone', 'like', "%$search%");
                                    });
                            })
                            ->when(request('from_date') && request('to_date'), function ($query) {
                                $query->whereBetween('return_date', [request('from_date'), request('to_date')]);
                            });

        $filtered_ids = (clone $filteredQuery)->pluck('id');
        $filtered_total_return = SaleReturnDetails::whereIn('sale_return_id', $filtered_ids)->sum('return_amount');
        $filtered_total_qty = SaleReturnDetails::whereIn('sale_return_id', $filtered_ids)->sum('return_qty');

        $data = $filteredQuery->latest()->paginate(10);

        $total_return = $filtered_total_return ?: $default_total_return;
        $total_qty = $filtered_total_qty ?: $default_total_qty;

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_return' => (float) $total_return,
            'total_qty' => (float) $total_qty,
            'data' => $data,
        ]);
    }

    public function purchaseReturnReport()
    {
        $baseQuery = PurchaseReturn::where('business_id', auth()->user()->business_id);

        $default_total_return = PurchaseReturnDetail::whereIn('purchase_return_id', $baseQuery->pluck('id'))->sum('return_amount');
        $default_total_qty = PurchaseReturnDetail::whereIn('purchase_return_id', $baseQuery->pluck('id'))->sum('return_qty');

        $filteredQuery = $baseQuery->select('id', 'purchase_id', 'return_date', 'invoice_no')
                            ->with([
                                'details:id,purchase_return_id,return_qty,return_amount',
                                'purchase:id,party_id,invoiceNumber,totalAmount',
                                'purchase.party:id,name',
                            ])
                            ->when(request('search'), function ($query) {
                                $search = request('search');
                                $query->where('invoice_no', 'like', "%$search%")
                                    ->orWhereHas('purchase.party', function ($subQuery) use ($search) {
                                        $subQuery->where('name', 'like', "%$search%")
                                            ->orWhere('phone', 'like', "%$search%");
                                    });
                            })
                            ->when(request('from_date') && request('to_date'), function ($query) {
                                $query->whereBetween('return_date', [request('from_date'), request('to_date')]);
                            });

        $filtered_ids = (clone $filteredQuery)->pluck('id');
        $filtered_total_return = PurchaseReturnDetail::whereIn('purchase_return_id', $filtered_ids)->sum('return_amount');
        $filtered_total_qty = PurchaseReturnDetail::whereIn('purchase_return_id', $filtered_ids)->sum('return_qty');

        $data = $filteredQuery->latest()->paginate(10);

        $total_return = $filtered_total_return ?: $default_total_return;
        $total_qty = $filtered_total_qty ?: $default_total_qty;

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_return' => (float) $total_return,
            'total_qty' => (float) $total_qty,
            'data' => $data,
        ]);
    }
}
