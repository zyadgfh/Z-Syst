<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GRNRequest;
use App\Http\Resources\GRNResource;
use App\Models\GoodsReceivedNote;
use App\Models\Party;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Services\GRNService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GRNController extends Controller
{
    protected GRNService $grnService;

    public function __construct(GRNService $grnService)
    {
        $this->grnService = $grnService;
    }

    /**
     * Display a listing of GRNs.
     */
    public function index(Request $request)
    {
        $query = GoodsReceivedNote::query()
            ->with(['supplier', 'purchaseOrder', 'items.product', 'receivedBy', 'verifiedBy'])
            ->forBusiness($request->user()->business_id);

        if ($request->has('supplier_id')) {
            $query->forSupplier($request->supplier_id);
        }

        if ($request->has('purchase_order_id')) {
            $query->forPurchaseOrder($request->purchase_order_id);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        $grns = $query->latest()->paginate($request->per_page ?? 15);

        if ($request->wantsJson()) {
            return GRNResource::collection($grns);
        }

        return view('admin.grn.index', compact('grns'));
    }

    /**
     * Show the form for creating a new GRN.
     */
    public function create(Request $request)
    {
        $suppliers = Party::where('type', 'supplier')
            ->forBusiness($request->user()->business_id)
            ->get();

        $purchaseOrders = PurchaseOrder::forBusiness($request->user()->business_id)
            ->whereIn('status', ['accepted', 'partially_received'])
            ->get();

        $products = Product::forBusiness($request->user()->business_id)
            ->active()
            ->get();

        return view('admin.grn.create', compact('suppliers', 'purchaseOrders', 'products'));
    }

    /**
     * Store a newly created GRN.
     */
    public function store(GRNRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['business_id'] = $request->user()->business_id;
        $validated['branch_id'] = $request->user()->branch_id;
        $validated['received_by'] = $request->user()->id;

        $grn = $this->grnService->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'GRN created successfully',
            'data' => new GRNResource($grn),
        ], 201);
    }

    /**
     * Display the specified GRN.
     */
    public function show(Request $request, GoodsReceivedNote $grn)
    {
        $grn->load(['supplier', 'purchaseOrder', 'items.product', 'items.qualityChecks', 'receivedBy', 'verifiedBy']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => new GRNResource($grn),
            ]);
        }

        return view('admin.grn.show', compact('grn'));
    }

    /**
     * Show the form for editing the specified GRN.
     */
    public function edit(Request $request, GoodsReceivedNote $grn)
    {
        $suppliers = Party::where('type', 'supplier')
            ->forBusiness($request->user()->business_id)
            ->get();

        $purchaseOrders = PurchaseOrder::forBusiness($request->user()->business_id)
            ->whereIn('status', ['accepted', 'partially_received'])
            ->get();

        $products = Product::forBusiness($request->user()->business_id)
            ->active()
            ->get();

        return view('admin.grn.edit', compact('grn', 'suppliers', 'purchaseOrders', 'products'));
    }

    /**
     * Update the specified GRN.
     */
    public function update(GRNRequest $request, GoodsReceivedNote $grn): JsonResponse
    {
        $validated = $request->validated();

        $grn = $this->grnService->update($grn, $validated);

        return response()->json([
            'success' => true,
            'message' => 'GRN updated successfully',
            'data' => new GRNResource($grn),
        ]);
    }

    /**
     * Remove the specified GRN.
     */
    public function destroy(GoodsReceivedNote $grn): JsonResponse
    {
        $this->grnService->delete($grn);

        return response()->json([
            'success' => true,
            'message' => 'GRN deleted successfully',
        ]);
    }

    /**
     * Verify GRN.
     */
    public function verify(Request $request, GoodsReceivedNote $grn): JsonResponse
    {
        $grn = $this->grnService->verify($grn, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'GRN verified successfully',
            'data' => new GRNResource($grn),
        ]);
    }

    /**
     * Accept GRN.
     */
    public function accept(Request $request, GoodsReceivedNote $grn): JsonResponse
    {
        $grn = $this->grnService->accept($grn);

        return response()->json([
            'success' => true,
            'message' => 'GRN accepted successfully',
            'data' => new GRNResource($grn),
        ]);
    }

    /**
     * Reject GRN.
     */
    public function reject(Request $request, GoodsReceivedNote $grn): JsonResponse
    {
        $grn = $this->grnService->reject($grn);

        return response()->json([
            'success' => true,
            'message' => 'GRN rejected successfully',
            'data' => new GRNResource($grn),
        ]);
    }

    /**
     * Get pending GRNs.
     */
    public function pending(Request $request): AnonymousResourceCollection
    {
        $grns = $this->grnService->getPending($request->user()->business_id);

        return GRNResource::collection($grns);
    }

    /**
     * Get GRN statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->grnService->getStatistics($request->user()->business_id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
