<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\SupplierLedger;
use App\Services\SupplierLedgerService;
use App\Services\SupplierPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierDashboardController extends Controller
{
    public function __construct(
        private SupplierLedgerService $ledgerService,
        private SupplierPaymentService $paymentService,
    ) {}

    /**
     * Supplier dashboard page.
     */
    public function index(Party $supplier)
    {
        $businessId = Auth::user()->business_id;

        // Ensure supplier belongs to current business
        if ($supplier->business_id !== $businessId || $supplier->type !== 'supplier') {
            abort(404);
        }

        $balance = $this->ledgerService->getBalance($businessId, $supplier->id);
        $summary = $this->ledgerService->getSummary($businessId, $supplier->id);
        $ledger = $this->ledgerService->getLedger($businessId, $supplier->id, 20);

        // Recent purchases (with eager-loaded relations for the view)
        $recentPurchases = Purchase::with('party')
            ->where('business_id', $businessId)
            ->where('party_id', $supplier->id)
            ->select('id', 'invoiceNumber', 'totalAmount', 'paidAmount', 'dueAmount', 'purchaseDate', 'status')
            ->latest()
            ->limit(10)
            ->get();

        // Recent returns
        $recentReturns = PurchaseReturn::where('business_id', $businessId)
            ->where('party_id', $supplier->id)
            ->select('id', 'invoice_no', 'credit_amount', 'return_date', 'status')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.suppliers.dashboard', compact(
            'supplier', 'balance', 'summary', 'ledger', 'recentPurchases', 'recentReturns'
        ));
    }

    /**
     * Get supplier ledger data (AJAX).
     */
    public function ledger(Party $supplier): JsonResponse
    {
        $businessId = Auth::user()->business_id;
        $ledger = $this->ledgerService->getLedger($businessId, $supplier->id, 100);

        return response()->json(['data' => $ledger]);
    }

    /**
     * Record a payment to supplier (AJAX).
     */
    public function recordPayment(Request $request, Party $supplier): JsonResponse
    {
        $businessId = Auth::user()->business_id;

        if ($supplier->business_id !== $businessId || $supplier->type !== 'supplier') {
            return response()->json(['success' => false, 'message' => __('Supplier not found.')], 404);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->paymentService->recordPayment([
                'party_id' => $supplier->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method ?? 'cash',
                'payment_date' => $request->payment_date ?? now()->toDateString(),
                'notes' => $request->notes,
            ], $businessId, Auth::id());

            return response()->json([
                'success' => true,
                'message' => __('Payment recorded successfully.'),
                'data' => [
                    'new_balance' => $result['new_balance'],
                    'party_due' => $result['party_due'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
