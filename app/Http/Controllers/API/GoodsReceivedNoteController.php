<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GoodsReceivedNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $grns = GoodsReceivedNote::forCompany($request->user()->company_id)
            ->with(['supplier:id,name', 'branch:id,name', 'purchaseOrder:id,po_number', 'receivedBy:id,name', 'items.product:id,productName'])
            ->when($request->supplier_id, fn($q, $v) => $q->where('supplier_id', $v))
            ->when($request->purchase_order_id, fn($q, $v) => $q->where('purchase_order_id', $v))
            ->when($request->branch_id, fn($q, $v) => $q->where('branch_id', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($grns);
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
            'items.*.quantity_received' => 'required|integer|min:1',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.cost_price' => 'nullable|numeric|min:0',
            'items.*.rack_location' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $grn = GoodsReceivedNote::create([
                'company_id' => $request->user()->company_id,
                'purchase_order_id' => $request->purchase_order_id,
                'supplier_id' => $request->supplier_id,
                'branch_id' => $request->branch_id,
                'grn_number' => 'GRN-' . strtoupper(uniqid()),
                'notes' => $request->notes,
                'received_by' => $request->user()->id,
            ]);

            foreach ($request->items as $item) {
                GrnItem::create([
                    'grn_id' => $grn->id,
                    'product_id' => $item['product_id'],
                    'quantity_received' => $item['quantity_received'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'cost_price' => $item['cost_price'] ?? 0,
                    'rack_location' => $item['rack_location'] ?? null,
                ]);

                // Update product stock
                $stock = ProductStock::firstOrNew([
                    'product_id' => $item['product_id'],
                    'branch_id' => $request->branch_id,
                    'batch_number' => $item['batch_number'] ?? null,
                ]);

                if ($stock->exists) {
                    $stock->increment('quantity', $item['quantity_received']);
                } else {
                    $stock->fill([
                        'company_id' => $request->user()->company_id,
                        'quantity' => $item['quantity_received'],
                        'reorder_level' => 10,
                        'reorder_quantity' => 50,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'is_active' => true,
                    ])->save();
                }
            }

            // Update purchase order quantities
            $purchaseOrder = PurchaseOrder::findOrFail($request->purchase_order_id);
            foreach ($request->items as $item) {
                $poItem = $purchaseOrder->items()
                    ->where('product_id', $item['product_id'])
                    ->first();

                if ($poItem) {
                    $poItem->increment('quantity_received', $item['quantity_received']);
                }
            }

            // Update PO status if all items received
            $allReceived = $purchaseOrder->items()
                ->whereColumn('quantity_received', '<', 'quantity_ordered')
                ->count() === 0;

            $purchaseOrder->update([
                'status' => $allReceived ? 'received' : 'partial',
            ]);

            DB::commit();

            return response()->json($grn->load(['supplier:id,name', 'branch:id,name', 'items.product:id,productName']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create goods received note: ' . $e->getMessage()], 422);
        }
    }

    public function show(Request $request, GoodsReceivedNote $goodsReceivedNote): JsonResponse
    {
        if ($goodsReceivedNote->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($goodsReceivedNote->load([
            'supplier:id,name,phone,email',
            'branch:id,name',
            'purchaseOrder:id,po_number,status',
            'purchaseOrder.items.product:id,productName',
            'receivedBy:id,name',
            'items.product:id,productName',
        ]));
    }
}