<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Models\DueCollect;
use App\Models\Expense;
use App\Models\FinancialAuditLog;
use App\Models\Income;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetails;
use App\Models\StockAudit;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function purchaseReport()
    {
        $request = ReportRequest::capture();
        $businessId = auth()->user()->business_id;

        // Generate cache key based on request parameters
        $cacheKey = $this->generateCacheKey('purchase_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $query = Purchase::select('id', 'party_id', 'invoiceNumber', 'purchaseDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType', 'note')
                ->with('party:id,name,phone')
                ->withCount('purchaseReturns')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $query->where(function ($subQuery) use ($request) {
                        $term = '%'.$request->input('search').'%';
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
                ->where('business_id', $businessId);

            $data = (clone $query)->latest()->paginate($request->input('per_page', 10));
            $total_paid = (clone $query)->sum('paidAmount');
            $total_due = (clone $query)->sum('dueAmount');

            return [
                'data' => $data,
                'total_paid' => $total_paid,
                'total_due' => $total_due,
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_paid' => $result['total_paid'],
            'total_due' => $result['total_due'],
            'data' => $result['data'],
        ]);
    }

    public function salesReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('sales_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $query = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType')
                ->with('party:id,name,phone')
                ->withCount('saleReturns')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%'.$request->input('search').'%';
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
                ->where('business_id', $businessId);

            $data = (clone $query)->latest()->paginate($request->input('per_page', 10));
            $total_paid = (clone $query)->sum('paidAmount');
            $total_due = (clone $query)->sum('dueAmount');

            return [
                'data' => $data,
                'total_paid' => $total_paid,
                'total_due' => $total_due,
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_paid' => $result['total_paid'],
            'total_due' => $result['total_due'],
            'data' => $result['data'],
        ]);
    }

    public function dueCollectsReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('due_collects_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $query = DueCollect::select('id', 'party_id', 'invoiceNumber', 'totalDue', 'dueAmountAfterPay', 'payDueAmount', 'paymentType', 'paymentDate')
                ->with('party:id,name,phone')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%'.$request->input('search').'%';
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
                ->where('business_id', $businessId);

            $data = (clone $query)->latest()->paginate($request->input('per_page', 10));
            $total_paid = (clone $query)->sum('payDueAmount');
            $total_due = (clone $query)->sum('totalDue');

            return [
                'data' => $data,
                'total_paid' => $total_paid,
                'total_due' => $total_due,
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_paid' => $result['total_paid'],
            'total_due' => $result['total_due'],
            'data' => $result['data'],
        ]);
    }

    public function lossProfitReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('loss_profit_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $query = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'dueAmount', 'lossProfit', 'totalAmount')
                ->where('business_id', $businessId)
                ->with('party:id,name,phone')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%'.$request->input('search').'%';
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

            $data = (clone $query)->latest()->paginate($request->input('per_page', 10));
            $total_profit = (clone $query)->where('lossProfit', '>', 0)->sum('lossProfit');
            $total_loss = (clone $query)->where('lossProfit', '<=', 0)->sum('lossProfit');

            return [
                'data' => $data,
                'total_profit' => $total_profit,
                'total_loss' => $total_loss,
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_profit' => $result['total_profit'],
            'total_loss' => $result['total_loss'],
            'data' => $result['data'],
        ]);
    }

    public function incomeReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('income_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $query = Income::with('category:id,categoryName')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%'.$request->input('search').'%';
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
                ->where('business_id', $businessId);

            $data = (clone $query)->latest()->paginate($request->input('per_page', 10));
            $total_income = (clone $query)->sum('amount');

            return [
                'data' => $data,
                'total_income' => $total_income,
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_income' => $result['total_income'],
            'data' => $result['data'],
        ]);
    }

    public function expenseReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('expense_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $query = Expense::with('category:id,categoryName')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%'.$request->input('search').'%';
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
                ->where('business_id', $businessId);

            $data = (clone $query)->latest()->paginate($request->input('per_page', 10));
            $total_expense = (clone $query)->sum('amount');

            return [
                'data' => $data,
                'total_expense' => $total_expense,
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_expense' => $result['total_expense'],
            'data' => $result['data'],
        ]);
    }

    public function lowStockReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('low_stock_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $query = DB::table('products')
                ->where('products.business_id', $businessId)
                ->join('stocks', 'products.id', '=', 'stocks.product_id')
                ->select('products.id', 'products.productName', 'products.productCode', 'products.alert_qty', 'products.sales_price', DB::raw('SUM(stocks.productStock) as totalStock'))
                ->groupBy('products.id', 'products.productName', 'products.productCode', 'products.alert_qty', 'products.sales_price')
                ->havingRaw('totalStock < alert_qty')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%'.$request->input('search').'%';
                    $query->where('productName', 'like', $term)
                        ->orWhere('productCode', 'like', $term);
                });

            $data = (clone $query)->paginate($request->input('per_page', 10));
            $total_item = (clone $query)->count();

            return [
                'data' => $data,
                'total_item' => $total_item,
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_expense' => $result['total_item'],
            'data' => $result['data'],
        ]);
    }

    public function taxesReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('taxes_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $query = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType')
                ->with('party:id,name,phone')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%'.$request->input('search').'%';
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
                ->where('business_id', $businessId);

            $data = (clone $query)->latest()->paginate($request->input('per_page', 10));
            $total_paid = (clone $query)->sum('paidAmount');
            $total_due = (clone $query)->sum('dueAmount');

            return [
                'data' => $data,
                'total_paid' => $total_paid,
                'total_due' => $total_due,
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_paid' => $result['total_paid'],
            'total_due' => $result['total_due'],
            'data' => $result['data'],
        ]);
    }

    public function saleReturnReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('sale_return_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $filteredQuery = SaleReturn::where('business_id', $businessId)
                ->select('id', 'sale_id', 'return_date', 'invoice_no')
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

            // Single query for both aggregate sums instead of separate queries
            $filteredIds = (clone $filteredQuery)->pluck('id');
            $totals = SaleReturnDetails::whereIn('sale_return_id', $filteredIds)
                ->selectRaw('COALESCE(SUM(return_amount), 0) as total_return, COALESCE(SUM(return_qty), 0) as total_qty')
                ->first();

            $data = $filteredQuery->latest()->paginate($request->input('per_page', 10));

            return [
                'data' => $data,
                'total_return' => (float) $totals->total_return,
                'total_qty' => (float) $totals->total_qty,
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_return' => $result['total_return'],
            'total_qty' => $result['total_qty'],
            'data' => $result['data'],
        ]);
    }

    public function purchaseReturnReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('purchase_return_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $filteredQuery = PurchaseReturn::where('business_id', $businessId)
                ->select('id', 'purchase_id', 'return_date', 'invoice_no')
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

            // Single query for both aggregate sums instead of separate queries
            $filteredIds = (clone $filteredQuery)->pluck('id');
            $totals = PurchaseReturnDetail::whereIn('purchase_return_id', $filteredIds)
                ->selectRaw('COALESCE(SUM(return_amount), 0) as total_return, COALESCE(SUM(return_qty), 0) as total_qty')
                ->first();

            $data = $filteredQuery->latest()->paginate($request->input('per_page', 10));

            return [
                'data' => $data,
                'total_return' => (float) $totals->total_return,
                'total_qty' => (float) $totals->total_qty,
            ];
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_return' => $result['total_return'],
            'total_qty' => $result['total_qty'],
            'data' => $result['data'],
        ]);
    }

    /**
     * Stock Audit Report
     */
    public function stockAuditReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('stock_audit_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $query = StockAudit::select('id', 'audit_number', 'audit_type', 'status', 'audit_date', 'completed_at', 'business_id', 'user_id')
                ->with('user:id,name')
                ->withCount('details')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%'.$request->input('search').'%';
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
                ->where('business_id', $businessId);

            $data = (clone $query)->latest()->paginate($request->input('per_page', 10));

            // Single aggregate query instead of separate count() calls with duplicated filters
            $stats = StockAudit::where('business_id', $businessId)
                ->when($request->filled('from_date') || $request->filled('to_date'), function ($q) use ($request) {
                    $q->whereBetween('audit_date', [$request->input('from_date'), $request->input('to_date')]);
                })
                ->selectRaw('COUNT(*) as total_audits, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_audits', ['completed'])
                ->first();

            return [
                'data' => $data,
                'total_audits' => (int) $stats->total_audits,
                'completed_audits' => (int) $stats->completed_audits,
            ];
        });

        return response()->json([
            'message' => __('Stock audit report fetched successfully.'),
            'total_audits' => $result['total_audits'],
            'completed_audits' => $result['completed_audits'],
            'data' => $result['data'],
        ]);
    }

    /**
     * Financial Audit Report
     */
    public function financialAuditReport(ReportRequest $request)
    {
        $businessId = auth()->user()->business_id;
        $cacheKey = $this->generateCacheKey('financial_audit_report', $businessId, $request);

        $result = $this->cacheService->remember($cacheKey, 300, function () use ($request, $businessId) {
            $query = FinancialAuditLog::select('id', 'audit_number', 'audit_type', 'start_date', 'end_date', 'status', 'opening_balance', 'closing_balance', 'variance', 'business_id', 'user_id')
                ->with('user:id,name')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%'.$request->input('search').'%';
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
                ->where('business_id', $businessId);

            $data = (clone $query)->latest()->paginate($request->input('per_page', 10));

            // Single aggregate query instead of separate count/sum calls with duplicated filters
            $stats = FinancialAuditLog::where('business_id', $businessId)
                ->when($request->filled('from_date') || $request->filled('to_date'), function ($q) use ($request) {
                    $q->whereBetween('start_date', [$request->input('from_date'), $request->input('to_date')]);
                })
                ->selectRaw('COUNT(*) as total_audits, COALESCE(SUM(CASE WHEN status = ? THEN variance ELSE 0 END), 0) as total_variance', ['completed'])
                ->first();

            return [
                'data' => $data,
                'total_audits' => (int) $stats->total_audits,
                'total_variance' => (float) $stats->total_variance,
            ];
        });

        return response()->json([
            'message' => __('Financial audit report fetched successfully.'),
            'total_audits' => $result['total_audits'],
            'total_variance' => $result['total_variance'],
            'data' => $result['data'],
        ]);
    }

    /**
     * Generate cache key based on request parameters
     */
    private function generateCacheKey(string $prefix, int $businessId, $request): string
    {
        $params = $request->only([
            'search', 'from_date', 'to_date', 'payment_status',
            'party_id', 'per_page', 'status', 'audit_type'
        ]);

        $paramsHash = md5(serialize($params));

        return "{$prefix}:{$businessId}:{$paramsHash}";
    }
}
