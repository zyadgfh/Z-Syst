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
use App\Models\StockAudit;
use App\Models\FinancialAuditLog;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\PurchaseReturnDetail;
use App\Http\Requests\ReportRequest;

class ReportsController extends Controller
{
    public function purchaseReport()
    {
        $request = ReportRequest::capture();

        $query = Purchase::select('id', 'party_id', 'invoiceNumber', 'purchaseDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType', 'note')
                    ->with('party:id,name,phone')
                    ->withCount('purchaseReturns')
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $query->where(function ($subQuery) use ($request) {
                            $term = '%' . $request->input('search') . '%';
                            $subQuery->where('paymentType', 'like', $term)
                                ->orWhere('invoiceNumber', 'like', $term)
                                ->orWhere('note', 'like', $term)
                                ->orWhereHas('party', function ($query) use ($term) {
                                    $query->where('name', 'like', $term)
                                        ->orWhere('phone', 'like', $term);
                                });
                        });
                    })
                    ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                        $query->whereBetween('purchaseDate', [$request->input('from_date'), $request->input('to_date')]);
                    })
                    ->when($request->input('payment_status') == 'paid', function ($query) {
                        $query->where('dueAmount', '<=', 0);
                    })
                    ->when($request->input('payment_status') == 'unpaid', function ($query) {
                        $query->where('dueAmount', '>', 0);
                    })
                    ->when($request->filled('party_id'), function ($query) use ($request) {
                        $query->where('party_id', $request->input('party_id'));
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

    public function salesReport(ReportRequest $request)
    {
        $query = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType')
                ->with('party:id,name,phone')
                ->withCount('saleReturns')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%' . $request->input('search') . '%';
                    $query->where(function ($subQuery) use ($term) {
                        $subQuery->where('paymentType', 'like', $term)
                            ->orWhere('invoiceNumber', 'like', $term)
                            ->orWhere('meta', 'like', $term)
                            ->orWhereHas('party', function ($query) use ($term) {
                                $query->where('name', 'like', $term)
                                    ->orWhere('phone', 'like', $term);
                            });
                    });
                })
                ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                    $query->whereBetween('saleDate', [$request->input('from_date'), $request->input('to_date')]);
                })
                ->when($request->input('payment_status') == 'paid', function ($query) {
                    $query->where('dueAmount', '<=', 0);
                })
                ->when($request->input('payment_status') == 'unpaid', function ($query) {
                    $query->where('dueAmount', '>', 0);
                })
                ->when($request->filled('party_id'), function ($query) use ($request) {
                    $query->where('party_id', $request->input('party_id'));
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

    public function dueCollectsReport(ReportRequest $request)
    {
        $query = DueCollect::select('id', 'party_id', 'invoiceNumber', 'totalDue', 'dueAmountAfterPay', 'payDueAmount', 'paymentType', 'paymentDate')
                    ->with('party:id,name,phone')
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $term = '%' . $request->input('search') . '%';
                        $query->where(function ($subQuery) use ($term) {
                            $subQuery->where('paymentType', 'like', $term)
                                ->orWhere('invoiceNumber', 'like', $term)
                                ->orWhereHas('party', function ($query) use ($term) {
                                    $query->where('name', 'like', $term)
                                        ->orWhere('phone', 'like', $term);
                                });
                        });
                    })
                    ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                        $query->whereBetween('paymentDate', [$request->input('from_date'), $request->input('to_date')]);
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

    public function lossProfitReport(ReportRequest $request)
    {
        $query = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'dueAmount', 'lossProfit', 'totalAmount')
                    ->where('business_id', auth()->user()->business_id)
                    ->with('party:id,name,phone')
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $term = '%' . $request->input('search') . '%';
                        $query->where(function ($subQuery) use ($term) {
                            $subQuery->where('invoiceNumber', 'like', $term)
                                ->orWhere('dueAmount', 'like', $term)
                                ->orWhere('meta', 'like', $term)
                                ->orWhereHas('party', function ($query) use ($term) {
                                    $query->where('name', 'like', $term)
                                        ->orWhere('phone', 'like', $term);
                                });
                        });
                    })
                    ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                        $query->whereBetween('saleDate', [$request->input('from_date'), $request->input('to_date')]);
                    })
                    ->when($request->input('payment_status') == 'paid', function ($query) {
                        $query->where('dueAmount', '<=', 0);
                    })
                    ->when($request->input('payment_status') == 'unpaid', function ($query) {
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

    public function incomeReport(ReportRequest $request)
    {
        $query = Income::with('category:id,categoryName')
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $term = '%' . $request->input('search') . '%';
                        $query->where(function ($subQuery) use ($term) {
                            $subQuery->where('amount', 'like', $term)
                                ->orWhere('incomeFor', 'like', $term)
                                ->orWhere('paymentType', 'like', $term)
                                ->orWhere('referenceNo', 'like', $term)
                                ->orWhere('note', 'like', $term)
                                ->orWhereHas('category', function ($query) use ($term) {
                                    $query->where('categoryName', 'like', $term);
                                });
                        });
                    })
                    ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                        $query->whereBetween('incomeDate', [$request->input('from_date'), $request->input('to_date')]);
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

    public function expenseReport(ReportRequest $request)
    {
        $query = Expense::with('category:id,categoryName')
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $term = '%' . $request->input('search') . '%';
                        $query->where(function ($subQuery) use ($term) {
                            $subQuery->where('amount', 'like', $term)
                                ->orWhere('expanseFor', 'like', $term)
                                ->orWhere('paymentType', 'like', $term)
                                ->orWhere('referenceNo', 'like', $term)
                                ->orWhere('note', 'like', $term)
                                ->orWhereHas('category', function ($query) use ($term) {
                                    $query->where('categoryName', 'like', $term);
                                });
                        });
                    })
                    ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                        $query->whereBetween('expenseDate', [$request->input('from_date'), $request->input('to_date')]);
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

    public function lowStockReport(ReportRequest $request)
    {
        $query = DB::table('products')
                    ->where('products.business_id', auth()->user()->business_id)
                    ->join('stocks', 'products.id', '=', 'stocks.product_id')
                    ->select('products.id', 'products.productName', 'products.productCode', 'products.alert_qty', 'products.sales_price', DB::raw('SUM(stocks.productStock) as totalStock'))
                    ->groupBy('products.id', 'products.productName', 'products.productCode', 'products.alert_qty', 'products.sales_price')
                    ->havingRaw('totalStock < alert_qty')
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $term = '%' . $request->input('search') . '%';
                        $query->where('productName', 'like', $term)
                            ->orWhere('productCode', 'like', $term);
                    });

        $data = (clone $query)->paginate(10);
        $total_item = (clone $query)->count();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_expense' => $total_item,
            'data' => $data,
        ]);
    }

    public function taxesReport(ReportRequest $request)
    {
        $query = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType')
                ->with('party:id,name,phone')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%' . $request->input('search') . '%';
                    $query->where(function ($subQuery) use ($term) {
                        $subQuery->where('paymentType', 'like', $term)
                            ->orWhere('invoiceNumber', 'like', $term)
                            ->orWhere('meta', 'like', $term)
                            ->orWhereHas('party', function ($query) use ($term) {
                                $query->where('name', 'like', $term)
                                    ->orWhere('phone', 'like', $term);
                            });
                    });
                })
                ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                    $query->whereBetween('saleDate', [$request->input('from_date'), $request->input('to_date')]);
                })
                ->when($request->input('payment_status') == 'paid', function ($query) {
                    $query->where('dueAmount', '<=', 0);
                })
                ->when($request->input('payment_status') == 'unpaid', function ($query) {
                    $query->where('dueAmount', '>', 0);
                })
                ->when($request->filled('party_id'), function ($query) use ($request) {
                    $query->where('party_id', $request->input('party_id'));
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

    public function saleReturnReport(ReportRequest $request)
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
                            ->when($request->filled('search'), function ($query) use ($request) {
                                $search = $request->input('search');
                                $query->where('invoice_no', 'like', "%$search%")
                                    ->orWhereHas('sale.party', function ($subQuery) use ($search) {
                                        $subQuery->where('name', 'like', "%$search%")
                                            ->orWhere('phone', 'like', "%$search%");
                                    });
                            })
                            ->when($request->filled('from_date') && $request->filled('to_date'), function ($query) use ($request) {
                                $query->whereBetween('return_date', [$request->input('from_date'), $request->input('to_date')]);
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

    public function purchaseReturnReport(ReportRequest $request)
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
                            ->when($request->filled('search'), function ($query) use ($request) {
                                $search = $request->input('search');
                                $query->where('invoice_no', 'like', "%$search%")
                                    ->orWhereHas('purchase.party', function ($subQuery) use ($search) {
                                        $subQuery->where('name', 'like', "%$search%")
                                            ->orWhere('phone', 'like', "%$search%");
                                    });
                            })
                            ->when($request->filled('from_date') && $request->filled('to_date'), function ($query) use ($request) {
                                $query->whereBetween('return_date', [$request->input('from_date'), $request->input('to_date')]);
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

    /**
     * Stock Audit Report
     */
public function stockAuditReport(ReportRequest $request)
    {
        $query = StockAudit::select('id', 'audit_number', 'audit_type', 'status', 'audit_date', 'completed_at', 'business_id', 'user_id')
                    ->with('user:id,name')
                    ->withCount('details')
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $term = '%' . $request->input('search') . '%';
                        $query->where(function ($subQuery) use ($term) {
                            $subQuery->where('audit_number', 'like', $term)
                                ->orWhere('audit_type', 'like', $term)
                                ->orWhere('status', 'like', $term);
                        });
                    })
                    ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                        $query->whereBetween('audit_date', [$request->input('from_date'), $request->input('to_date')]);
                    })
                    ->when($request->filled('status'), function ($query) use ($request) {
                        $query->where('status', $request->input('status'));
                    })
                    ->when($request->filled('audit_type'), function ($query) use ($request) {
                        $query->where('audit_type', $request->input('audit_type'));
                    })
                    ->where('business_id', auth()->user()->business_id);

        $data = (clone $query)->latest()->paginate(10);

        // Calculate summary statistics
        $total_audits = StockAudit::where('business_id', auth()->user()->business_id)
            ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                $query->whereBetween('audit_date', [$request->input('from_date'), $request->input('to_date')]);
            })
            ->count();

        $completed_audits = StockAudit::where('business_id', auth()->user()->business_id)
            ->where('status', 'completed')
            ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                $query->whereBetween('audit_date', [$request->input('from_date'), $request->input('to_date')]);
            })
            ->count();

        return response()->json([
            'message' => __('Stock audit report fetched successfully.'),
            'total_audits' => $total_audits,
            'completed_audits' => $completed_audits,
            'data' => $data,
        ]);
    }

    /**
     * Financial Audit Report
     */
    public function financialAuditReport(ReportRequest $request)
    {
        $query = FinancialAuditLog::select('id', 'audit_number', 'audit_type', 'start_date', 'end_date', 'status', 'opening_balance', 'closing_balance', 'variance', 'business_id', 'user_id')
                    ->with('user:id,name')
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $term = '%' . $request->input('search') . '%';
                        $query->where(function ($subQuery) use ($term) {
                            $subQuery->where('audit_number', 'like', $term)
                                ->orWhere('audit_type', 'like', $term)
                                ->orWhere('status', 'like', $term);
                        });
                    })
                    ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                        $query->whereBetween('start_date', [$request->input('from_date'), $request->input('to_date')]);
                    })
                    ->when($request->filled('status'), function ($query) use ($request) {
                        $query->where('status', $request->input('status'));
                    })
                    ->when($request->filled('audit_type'), function ($query) use ($request) {
                        $query->where('audit_type', $request->input('audit_type'));
                    })
                    ->where('business_id', auth()->user()->business_id);

        $data = (clone $query)->latest()->paginate(10);

        // Calculate summary statistics
        $total_audits = FinancialAuditLog::where('business_id', auth()->user()->business_id)
            ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->input('from_date'), $request->input('to_date')]);
            })
            ->count();

        $total_variance = FinancialAuditLog::where('business_id', auth()->user()->business_id)
            ->where('status', 'completed')
            ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->input('from_date'), $request->input('to_date')]);
            })
            ->sum('variance');

        return response()->json([
            'message' => __('Financial audit report fetched successfully.'),
            'total_audits' => $total_audits,
            'total_variance' => $total_variance,
            'data' => $data,
        ]);
    }
}
