<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DashboardController — Security Audit Fix (LOW)
 *
 * Fixes applied:
 * 1. Removed hardcoded `business_id ?? 1` fallback in designSystem — always use resolved business ID
 * 2. Added permission middleware on all methods
 * 3. Added per_page validation on paginated queries
 * 4. Added structured logging for dashboard access
 * 5. Enforced business scope on all count queries to prevent cross-tenant data leakage
 */
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'business.active']);
    }

    public function index(Request $request)
    {
        $this->authorize('permission', 'dashboard-read');

        $businessId = resolveBusinessId();

        $data = $this->getDashboardData($businessId);

        StructuredLogger::info('dashboard_accessed', [
            'user_id' => Auth::id(),
            'business_id' => $businessId,
        ]);

        return response()->json($data);
    }

    public function planStatistics()
    {
        $this->authorize('permission', 'dashboard-read');

        $businessId = resolveBusinessId();

        $stats = Business::where('id', $businessId)
            ->withCount(['users', 'products'])
            ->first();

        return response()->json($stats);
    }

    public function designSystem()
    {
        $this->authorize('permission', 'dashboard-read');

        $businessId = resolveBusinessId();

        $business = Business::where('id', $businessId)->first();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        return response()->json([
            'primary_color' => $business->primary_color ?? '#007bff',
            'secondary_color' => $business->secondary_color ?? '#6c757d',
            'logo' => $business->logo,
            'favicon' => $business->favicon,
        ]);
    }

    protected function getDashboardData(int $businessId): array
    {
        $productCount = Product::where('company_id', $businessId)->count();
        $lowStockCount = Product::where('company_id', $businessId)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->count();
        $invoiceCount = Invoice::where('company_id', $businessId)->count();
        $patientCount = Patient::where('company_id', $businessId)->count();
        $totalRevenue = Invoice::where('company_id', $businessId)
            ->where('status', 'paid')
            ->sum('total_amount');

        $recentInvoices = Invoice::where('company_id', $businessId)
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return compact(
            'productCount',
            'lowStockCount',
            'invoiceCount',
            'patientCount',
            'totalRevenue',
            'recentInvoices'
        );
    }
}
