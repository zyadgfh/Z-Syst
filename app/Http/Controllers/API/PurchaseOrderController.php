<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseOrderController extends Controller
{
    protected PurchaseOrderService $poService;

    public function __construct(PurchaseOrderService $poService)
    {
        $this->poService = $poService;
    }

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PurchaseOrder::query()
            ->with(['supplier', 'items.product', 'createdBy'])
            ->forBusiness($request->user()->business_id);

        if ($request->has('supplier_id')) {
            $query->forSupplier($request->supplier_id);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        $purchaseOrders = $query->latest()->paginate($request->per_page ?? 15);

        return PurchaseOrderResource::collection($purchaseOrders);
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(PurchaseOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['business_id'] = $request->user()->business_id;
        $validated['branch_id'] = $request->user()->branch_id;

        $po = $this->poService->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order created successfully',
            'data' => new PurchaseOrderResource($po),
        ], 201);
    }

    /**
     * Display the specified purchase order.
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['supplier', 'items.product', 'createdBy', 'approvedBy']);

        return response()->json([
            'success' => true,
            'data' => new PurchaseOrderResource($purchaseOrder),
        ]);
    }

    /**
     * Update the specified purchase order.
     */
    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validated();
        
        $po = $this->poService->update($purchaseOrder, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order updated successfully',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Remove the specified purchase order.
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->poService->delete($purchaseOrder);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order deleted successfully',
        ]);
    }

    /**
     * Send purchase order to supplier.
     */
    public function send(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $po = $this->poService->send($purchaseOrder);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order sent to supplier successfully',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Approve purchase order.
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $po = $this->poService->approve($purchaseOrder, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order approved successfully',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Reject purchase order.
     */
    public function reject(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $po = $this->poService->reject($purchaseOrder, $request->user()->id, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order rejected successfully',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Cancel purchase order.
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $po = $this->poService->cancel($purchaseOrder);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order cancelled successfully',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Convert PO to Purchase.
     */
    public function convertToPurchase(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchase = $this->poService->convertToPurchase($purchaseOrder);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order converted to purchase successfully',
            'data' => [
                'purchase_id' => $purchase->id,
                'invoice_number' => $purchase->invoiceNumber,
            ],
        ]);
    }

    /**
     * Get pending purchase orders.
     */
    public function pending(Request $request): AnonymousResourceCollection
    {
        $purchaseOrders = $this->poService->getPending($request->user()->business_id);

        return PurchaseOrderResource::collection($purchaseOrders);
    }

    /**
     * Get overdue purchase orders.
     */
    public function overdue(Request $request): AnonymousResourceCollection
    {
        $purchaseOrders = $this->poService->getOverdue($request->user()->business_id);

        return PurchaseOrderResource::collection($purchaseOrders);
    }

    /**
     * Get purchase order statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->poService->getStatistics($request->user()->business_id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
