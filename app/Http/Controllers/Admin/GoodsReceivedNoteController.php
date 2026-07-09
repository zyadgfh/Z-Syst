<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\API\BaseController;
use App\Models\GoodsReceivedNote;
use App\Services\GoodsReceivedNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class GoodsReceivedNoteController extends BaseController
{
    public function __construct(
        private readonly GoodsReceivedNoteService $goodsReceivedNoteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $notes = GoodsReceivedNote::forCompany($request->user()->company_id)
            ->with(['supplier', 'branch', 'purchaseOrder', 'receivedBy'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success($notes);
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

        $note = $this->goodsReceivedNoteService->create(
            $data,
            $items,
            $request->user()->company_id,
            $request->user()->id,
        );

        return $this->created($note, 'Goods received note created successfully');
    }

    public function show(Request $request, GoodsReceivedNote $goodsReceivedNote): JsonResponse
    {
        if ($goodsReceivedNote->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success(
            $goodsReceivedNote->load(['supplier', 'branch', 'purchaseOrder', 'items.product', 'receivedBy'])
        );
    }

    public function destroy(Request $request, GoodsReceivedNote $goodsReceivedNote): JsonResponse
    {
        if ($goodsReceivedNote->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $goodsReceivedNote->delete();

        return $this->success(null, 'Goods received note deleted successfully');
    }

    private function validateStoreRequest(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_number' => 'nullable|string|max:255',
            'items.*.quantity_received' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.manufacturing_date' => 'nullable|date',
            'items.*.rack_location' => 'nullable|string|max:255',
        ]);
    }
}
