<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = PurchaseOrder::forCompany($request->user()->company_id)
            ->with(['supplier', 'branch', 'items.product'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->supplier_id, fn($q, $v) => $q->where('supplier_id', $v))
            ->when($request->branch_id, fn($q, $v) => $q->where('branch_id', $v))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'expected_delivery_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $purchaseOrder = $this->purchaseOrderService->create(
            $validator->validated(),
            $request->items,
            $request->user()->company_id,
            $request->user()->id
        );

        return response()->json($purchaseOrder, 201);
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $purchaseOrder = $this->purchaseOrderService->approve($purchaseOrder, $request->user()->id);
        return response()->json($purchaseOrder);
    }

    public function send(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $purchaseOrder = $this->purchaseOrderService->send($purchaseOrder);
        return response()->json($purchaseOrder);
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $purchaseOrder = $this->purchaseOrderService->cancel($purchaseOrder);
        return response()->json($purchaseOrder);
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($purchaseOrder->load(['supplier', 'branch', 'items.product']));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return response()->json(['message' => 'Cannot modify a non-draft purchase order'], 422);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $purchaseOrder->update($validator->validated());
        return response()->json($purchaseOrder);
    }

    public function destroy(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return response()->json(['message' => 'Cannot delete a non-draft purchase order'], 422);
        }

        $purchaseOrder->delete();
        return response()->json(['message' => 'Purchase order deleted successfully']);
    }
}
