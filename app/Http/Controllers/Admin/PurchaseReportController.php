<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:reports-view');
    }

    /**
     * Purchase reports overview page.
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $fromDate = $request->get('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        $purchases = Purchase::where('business_id', $businessId)
            ->whereBetween('purchaseDate', [$fromDate, $toDate]);

        $totalPurchases = (clone $purchases)->count();
        $totalAmount = (clone $purchases)->sum('totalAmount');
        $totalPaid = (clone $purchases)->sum('paidAmount');
        $totalDue = (clone $purchases)->sum('dueAmount');

        // By supplier
        $bySupplier = (clone $purchases)
            ->select('party_id', DB::raw('count(*) as count'), DB::raw('sum(totalAmount) as total'))
            ->groupBy('party_id')
            ->with('party:id,name')
            ->get();

        // By branch
        $byBranch = (clone $purchases)
            ->select('branch_id', DB::raw('count(*) as count'), DB::raw('sum(totalAmount) as total'))
            ->groupBy('branch_id')
            ->with('branch:id,branch_name')
            ->get();

        // Purchase returns
        $returns = PurchaseReturn::where('business_id', $businessId)
            ->whereBetween('return_date', [$fromDate, $toDate]);
        $totalReturns = (clone $returns)->count();
        $totalReturnAmount = (clone $returns)->sum('credit_amount');

        $suppliers = Party::where('business_id', $businessId)->where('type', 'supplier')->select('id', 'name')->get();
        $branches = Branch::where('company_id', $businessId)->where('is_active', true)->select('id', 'branch_name')->get();

        return view('admin.purchases.reports', compact(
            'totalPurchases', 'totalAmount', 'totalPaid', 'totalDue',
            'bySupplier', 'byBranch', 'totalReturns', 'totalReturnAmount',
            'suppliers', 'branches', 'fromDate', 'toDate'
        ));
    }

    /**
     * Supplier balance report.
     */
    public function supplierBalance(Request $request)
    {
        $businessId = Auth::user()->business_id;

        $suppliers = Party::where('business_id', $businessId)
            ->where('type', 'supplier')
            ->select('id', 'name', 'phone', 'due', 'status')
            ->orderBy('due', 'desc')
            ->get();

        $totalBalance = $suppliers->sum('due');

        return view('admin.purchases.supplier-balance', compact('suppliers', 'totalBalance'));
    }

    /**
     * Stock movement report.
     */
    public function stockMovements(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $fromDate = $request->get('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        $movements = StockMovement::where('business_id', $businessId)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->with('product:id,productName', 'user:id,name')
            ->latest()
            ->paginate(50);

        // Summary
        $summary = StockMovement::where('business_id', $businessId)
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->selectRaw('movement_type, count(*) as count, sum(quantity) as total_qty')
            ->groupBy('movement_type')
            ->get();

        return view('admin.purchases.stock-movements', compact('movements', 'summary', 'fromDate', 'toDate'));
    }
}
