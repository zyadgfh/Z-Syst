<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnController extends Controller
{
    public function __construct(private PurchaseReturnService $returnService)
    {
        $this->middleware('permission:purchases-view')->only('index', 'show');
        $this->middleware('permission:purchases-create')->only('create', 'store');
    }

    /**
     * Purchase returns list page.
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $filters = $request->only(['purchase_id', 'party_id', 'from_date', 'to_date']);
        $returns = $this->returnService->list($filters, $businessId, 15);

        return view('admin.purchases.returns-index', compact('returns'));
    }

    /**
     * Create purchase return page.
     */
    public function create(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $purchaseId = $request->get('purchase_id');

        if (!$purchaseId) {
            return redirect()->route('admin.purchases.index')
                ->with('error', __('Please select a purchase invoice to create a return.'));
        }

        $purchase = Purchase::with(['party:id,name', 'details.product:id,productName'])
            ->where('business_id', $businessId)
            ->findOrFail($purchaseId);

        // Check if purchase can be returned
        if ($purchase->status === 'canceled') {
            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('error', __('Cannot create returns for a cancelled purchase.'));
        }

        $returnableItems = $this->returnService->getReturnableItems($purchaseId);

        // Check if there are any returnable items
        $hasReturnable = collect($returnableItems)->contains('returnable_qty', '>', 0);
        if (!$hasReturnable) {
            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('error', __('All items in this purchase have already been returned.'));
        }

        return view('admin.purchases.returns-create', compact('purchase', 'returnableItems'));
    }

    /**
     * Store purchase return via AJAX.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'items' => 'required|array|min:1',
            'items.*.purchase_detail_id' => 'required|exists:purchase_details,id',
            'items.*.return_qty' => 'required|integer|min:1',
            'items.*.discount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $purchase = Purchase::where('business_id', Auth::user()->business_id)
                ->findOrFail($request->purchase_id);

            $return = $this->returnService->processReturn(
                $purchase,
                $request->all(),
                Auth::user()->business_id,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => __('Purchase return created successfully.'),
                'data' => [
                    'id' => $return->id,
                    'invoice_no' => $return->invoice_no,
                    'credit_amount' => $return->credit_amount,
                ],
                'redirect' => route('admin.purchases.returns.show', $return->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show purchase return details.
     */
    public function show(PurchaseReturn $purchaseReturn)
    {
        // Ensure the return belongs to the current business
        if ($purchaseReturn->business_id !== Auth::user()->business_id) {
            abort(404);
        }

        $data = $this->returnService->show($purchaseReturn->id);

        return view('admin.purchases.returns-show', compact('data'));
    }
}
