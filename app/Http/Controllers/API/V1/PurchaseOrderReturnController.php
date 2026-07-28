<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Http\Resources\PurchaseOrderReturnResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReturn;
use App\Services\PurchaseOrderReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderReturnController extends BaseController
{
    public function __construct(
        private readonly PurchaseOrderReturnService $purchaseOrderReturnService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $returns = PurchaseOrderReturn::forCompany($request->user()->company_id)
            ->with(['purchaseOrder', 'supplier', 'branch', 'items.product', 'createdBy'])
            ->when($request->purchase_order_id, fn ($q, $v) => $q->where('purchase_order_id', $v))
            ->when($request->supplier_id, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return $this->success($returns);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_returned' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $items = $data['items'];
        unset($data['items']);

        $return = $this->purchaseOrderReturnService->create(
            $data,
            $items,
            $request->user()->company_id,
            $request->user()->id,
        );

        return $this->created(new PurchaseOrderReturnResource($return), 'Purchase order return created successfully');
    }

    public function show(Request $request, PurchaseOrderReturn $purchaseOrderReturn): JsonResponse
    {
        if ($purchaseOrderReturn->company_id !== $request->user()->company_id) {
            return $this->forbidden('Forbidden');
        }

        return $this->success(
            new PurchaseOrderReturnResource(
                $purchaseOrderReturn->load(['purchaseOrder', 'supplier', 'branch', 'items.product', 'createdBy'])
            )
        );
    }

    public function update(Request $request, PurchaseOrderReturn $purchaseOrderReturn): JsonResponse
    {
        if ($purchaseOrderReturn->company_id !== $request->user()->company_id) {
            return $this->forbidden('Forbidden');
        }

        if (! $purchaseOrderReturn->canEdit()) {
            return $this->error('Cannot modify a non-draft purchase order return', 422);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_returned' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $return = $this->purchaseOrderReturnService->update($purchaseOrderReturn, $data, $items);

        return $this->success(new PurchaseOrderReturnResource($return), 'Purchase order return updated successfully');
    }

    public function destroy(Request $request, PurchaseOrderReturn $purchaseOrderReturn): JsonResponse
    {
        if ($purchaseOrderReturn->company_id !== $request->user()->company_id) {
            return $this->forbidden('Forbidden');
        }

        $this->purchaseOrderReturnService->destroy($purchaseOrderReturn);

        return $this->deleted('Purchase order return deleted successfully');
    }
}
