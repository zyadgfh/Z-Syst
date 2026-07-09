<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReturn;
use App\Services\PurchaseOrderReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class PurchaseOrderReturnController extends BaseController
{
    public function __construct(
        private readonly PurchaseOrderReturnService $purchaseOrderReturnService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateStoreRequest($request);
        if ($validated->fails()) {
            return $this->validationError($validated->errors()->toArray());
        }

        $data = $validated->validated();
        $items = $data['items'];
        unset($data['items']);

        $return = $this->purchaseOrderReturnService->create(
            $data,
            $items,
            $request->user()->company_id,
            $request->user()->id,
        );

        return $this->created($return, 'Purchase order return created successfully');
    }

    public function index(Request $request): JsonResponse
    {
        $returns = PurchaseOrderReturn::forCompany($request->user()->company_id)
            ->with(['purchaseOrder', 'supplier', 'branch', 'items.product', 'createdBy'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success($returns);
    }

    public function show(Request $request, PurchaseOrderReturn $purchaseOrderReturn): JsonResponse
    {
        if ($purchaseOrderReturn->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success($purchaseOrderReturn->load(['purchaseOrder', 'supplier', 'branch', 'items.product', 'createdBy']));
    }

    public function destroy(Request $request, PurchaseOrderReturn $purchaseOrderReturn): JsonResponse
    {
        if ($purchaseOrderReturn->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $purchaseOrderReturn->delete();

        return $this->success(null, 'Purchase order return deleted successfully');
    }

    private function validateStoreRequest(Request $request): \Illuminate\Validation\Validator
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

        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->any()) {
                return;
            }

            $purchaseOrder = PurchaseOrder::forCompany($request->user()->company_id)
                ->with('items')
                ->find($request->input('purchase_order_id'));

            if (! $purchaseOrder) {
                $validator->errors()->add('purchase_order_id', 'Invalid purchase order.');
                return;
            }

            foreach ($request->input('items', []) as $index => $item) {
                $purchaseOrderItem = $purchaseOrder->items->first(fn($poItem) => $poItem->product_id === $item['product_id']);

                if (! $purchaseOrderItem) {
                    $validator->errors()->add("items.$index.product_id", 'Product is not part of the purchase order.');
                    continue;
                }

                if ($item['quantity_returned'] > $purchaseOrderItem->quantity_received) {
                    $validator->errors()->add("items.$index.quantity_returned", 'Return quantity cannot exceed received quantity.');
                }
            }
        });

        return $validator;
    }
}
