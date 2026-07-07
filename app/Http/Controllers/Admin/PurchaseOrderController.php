<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class PurchaseOrderController extends BaseController
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $purchaseOrders = PurchaseOrder::forCompany($request->user()->company_id)
            ->with(['supplier', 'branch', 'createdBy'])
            ->when($request->status, fn($query, $status) => $query->where('status', $status))
            ->when($request->supplier_id, fn($query, $id) => $query->where('supplier_id', $id))
            ->when($request->from_date, fn($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->to_date, fn($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success($purchaseOrders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateStoreRequest($request);
        if ($validated->fails()) {
            return $this->error($validated->errors()->first(), 422);
        }

        $data = $validated->validated();
        $items = $data['items'];
        unset($data['items']);

        $purchaseOrder = $this->purchaseOrderService->create(
            $data,
            $items,
            $request->user()->company_id,
            $request->user()->id,
        );

        return $this->created($purchaseOrder, 'Purchase order created successfully');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success(
            $purchaseOrder->load(['supplier', 'branch', 'items.product', 'createdBy', 'approvedBy', 'goodsReceivedNotes'])
        );
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        if (!in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return $this->error('Cannot modify a non-draft purchase order', 422);
        }

        $validated = Validator::make($request->all(), [
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors()->first(), 422);
        }

        $purchaseOrder->update($validated->validated());

        return $this->success($purchaseOrder, 'Purchase order updated successfully');
    }

    public function destroy(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $purchaseOrder->delete();

        return $this->success(null, 'Purchase order deleted successfully');
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $purchaseOrder = $this->purchaseOrderService->approve($purchaseOrder, $request->user()->id);

        return $this->success($purchaseOrder, 'Purchase order approved successfully');
    }

    public function send(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $purchaseOrder = $this->purchaseOrderService->send($purchaseOrder);

        return $this->success($purchaseOrder, 'Purchase order sent successfully');
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $purchaseOrder = $this->purchaseOrderService->cancel($purchaseOrder);

        return $this->success($purchaseOrder, 'Purchase order cancelled successfully');
    }

    private function validateStoreRequest(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ]);
    }
}