<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GRNRequest;
use App\Http\Resources\GRNResource;
use App\Http\Resources\GRNItemResource;
use App\Models\GoodsReceivedNote;
use App\Models\Barcode;
use App\Models\Product;
use App\Services\GRNService;
use App\Services\DeviceSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GRNController extends Controller
{
    protected GRNService $grnService;

    public function __construct(
        GRNService $grnService,
        protected \App\Services\BarcodeService $barcodeService,
    ) {
        $this->grnService = $grnService;

        $this->middleware('permission:grn-read')->only(['index', 'show', 'pending', 'statistics']);
        $this->middleware('permission:grn-create')->only(['create', 'store']);
        $this->middleware('permission:grn-update')->only(['update', 'edit', 'verify', 'accept', 'reject']);
        $this->middleware('permission:grn-delete')->only(['destroy']);
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
        $suppliers = \App\Models\Party::where('type', 'supplier')
            ->forBusiness($request->user()->business_id)
            ->get();

        $purchaseOrders = \App\Models\PurchaseOrder::forBusiness($request->user()->business_id)
            ->whereIn('status', ['accepted', 'partially_received'])
            ->get();

        $products = \App\Models\Product::forBusiness($request->user()->business_id)
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
        $suppliers = \App\Models\Party::where('type', 'supplier')
            ->forBusiness($request->user()->business_id)
            ->get();

        $purchaseOrders = \App\Models\PurchaseOrder::forBusiness($request->user()->business_id)
            ->whereIn('status', ['accepted', 'partially_received'])
            ->get();

        $products = \App\Models\Product::forBusiness($request->user()->business_id)
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

        // Owner workflow: finishing a purchase asks to print barcode labels
        // (toggle: print.barcode_on_purchase_complete). Mint one product-level
        // barcode per received item (reusing an active one when present) and
        // hand back a ready label sheet URL for the client to open.
        $labelsUrl = null;
        $labelsCount = 0;
        if (DeviceSettings::get((int) $request->user()->business_id, 'print.barcode_on_purchase_complete', true)) {
            $labelsUrl = $this->mintLabelsForGrn($grn, $request->user()->business_id, $labelsCount);
        }

        return response()->json([
            'success' => true,
            'message' => 'GRN accepted successfully',
            'data' => new GRNResource($grn),
            'print_barcodes_url' => $labelsUrl,
            'print_barcodes_count' => $labelsCount,
        ]);
    }

    /** Mint/reuse product barcodes for GRN items → printable sheet URL. */
    protected function mintLabelsForGrn(GoodsReceivedNote $grn, int $businessId, int &$count): ?string
    {
        $cap = (int) DeviceSettings::get($businessId, 'print.max_labels_per_item', 10);
        $ids = [];
        $type = DeviceSettings::get($businessId, 'print.default_symbology', 'EAN13');

        foreach ($grn->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $barcode = Barcode::forBusiness($businessId)->active()
                ->whereNull('batch_id')
                ->where('barcode_type', $type)
                ->where('product_id', $item->product_id)
                ->first()
                ?? $this->barcodeService->generateForProduct(
                    Product::findOrFail($item->product_id),
                    ['barcode_type' => $type]
                );

            $copies = min(max(1, (int) $item->accepted_quantity), $cap);
            for ($i = 0; $i < $copies; $i++) {
                $ids[] = $barcode->id;
            }
            $count += $copies;
        }

        return $ids ? route('admin.barcodes.labels', ['ids' => implode(',', $ids)]) : null;
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
