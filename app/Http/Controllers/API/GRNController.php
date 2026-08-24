<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GRNRequest;
use App\Http\Resources\GRNResource;
use App\Models\GoodsReceivedNote;
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
    public function index(Request $request): AnonymousResourceCollection
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

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $grns = $query->latest()->paginate($request->per_page ?? 15);

        return GRNResource::collection($grns);
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
    public function show(Request $request, GoodsReceivedNote $grn): JsonResponse
    {
        if ($grn->business_id !== $request->user()->business_id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $grn->load(['supplier', 'purchaseOrder', 'items.product', 'items.qualityChecks', 'receivedBy', 'verifiedBy']);

        return response()->json([
            'success' => true,
            'data' => new GRNResource($grn),
        ]);
    }

    /**
     * Update the specified GRN.
     */
    public function update(GRNRequest $request, GoodsReceivedNote $grn): JsonResponse
    {
        if (! $this->grnService->canBeEdited($grn)) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or pending GRNs can be updated',
            ], 403);
        }

        $validated = $request->validated();
        $validated['warehouse_id'] = $grn->warehouse_id;

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
        try {
            $this->grnService->delete($grn);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }

        return response()->json(null, 204);
    }

    /**
     * Verify GRN.
     */
    public function verify(Request $request, GoodsReceivedNote $grn): JsonResponse
    {
        try {
            $grn = $this->grnService->verify($grn, $request->user()->id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }

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
        try {
            $grn = $this->grnService->accept($grn);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }

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
        try {
            $grn = $this->grnService->reject($grn);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }

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
