<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * StockTransferController — inter-warehouse transfer flow (C3).
 *
 * Lifecycle: pending → shipped → received (or cancelled while pending).
 * Receiving moves warehouse_stocks quantities and writes matching
 * stock_movements rows so the audit trail (reference_type=stock_transfer)
 * stays complete.
 */
class StockTransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $businessId = auth()->user()->business_id;

        $transfers = StockTransfer::where('business_id', $businessId)
            ->with(['product:id,product_name', 'fromWarehouse:id,name', 'toWarehouse:id,name'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $transfers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $businessId = auth()->user()->business_id;

        $validated = $request->validate([
            'from_warehouse_id' => 'required|integer|exists:warehouses,id',
            'to_warehouse_id' => 'required|integer|exists:warehouses,id|different:from_warehouse_id',
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $available = (int) WarehouseStock::where('business_id', $businessId)
            ->where('warehouse_id', $validated['from_warehouse_id'])
            ->where('product_id', $validated['product_id'])
            ->value('quantity');

        if ($available < $validated['quantity']) {
            return response()->json([
                'message' => __('Insufficient stock in the source warehouse.'),
                'available' => $available,
            ], 422);
        }

        // Path B: stock_transfers.user_id is a legacy integer FK; uuid
        // identities (app_users) must not hit it — keep the column null and
        // attribute the actor in notes (same convention as sales.meta).
        $actorId = auth()->id();
        $transfer = StockTransfer::create($validated + [
            'business_id' => $businessId,
            'user_id' => is_int($actorId) ? $actorId : (ctype_digit((string) $actorId) ? (int) $actorId : null),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => __('Transfer created successfully.'),
            'data' => $transfer->load(['product:id,product_name', 'fromWarehouse:id,name', 'toWarehouse:id,name']),
        ], 201);
    }

    public function show(StockTransfer $transfer): JsonResponse
    {
        abort_unless($transfer->business_id === auth()->user()->business_id, 404);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $transfer->load(['product:id,product_name', 'fromWarehouse:id,name', 'toWarehouse:id,name']),
        ]);
    }

    /**
     * Advance the transfer lifecycle: pending → shipped → received.
     * Shipping decrements the source warehouse and receiving increments the target;
     * each leg logs one stock_movements row with movement_type 'transfer', which
     * ProductAnalyticsService counts as net-neutral for the business.
     */
    public function transition(Request $request, StockTransfer $transfer): JsonResponse
    {
        abort_unless($transfer->business_id === auth()->user()->business_id, 404);

        $validated = $request->validate([
            'action' => 'required|in:ship,receive,cancel',
        ]);

        $action = $validated['action'];

        if ($action === 'cancel') {
            if ($transfer->status !== 'pending') {
                return response()->json(['message' => __('Only pending transfers can be cancelled.')], 422);
            }
            $transfer->update(['status' => 'cancelled']);

            return response()->json(['message' => __('Transfer cancelled.'), 'data' => $transfer]);
        }

        if ($action === 'ship') {
            if ($transfer->status !== 'pending') {
                return response()->json(['message' => __('Only pending transfers can be shipped.')], 422);
            }

            DB::transaction(function () use ($transfer) {
                $source = WarehouseStock::where('business_id', $transfer->business_id)
                    ->where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $transfer->product_id)
                    ->first();

                $before = (int) ($source->quantity ?? 0);

                if ($source) {
                    $source->decrement('quantity', $transfer->quantity);
                }

                // Without this row the outbound leg of a transfer left no trace:
                // stock_movements is the only audit trail, and the receiving leg
                // alone cannot show where the quantity went.
                StockMovement::create([
                    'business_id' => $transfer->business_id,
                    'product_id' => $transfer->product_id,
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'user_id' => auth()->id(),
                    'movement_type' => 'transfer',
                    'quantity' => -$transfer->quantity,
                    'before_quantity' => $before,
                    'after_quantity' => $before - $transfer->quantity,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'notes' => 'Stock transfer shipped #' . $transfer->id,
                ]);

                $transfer->update(['status' => 'shipped']);
            });

            return response()->json(['message' => __('Transfer shipped.'), 'data' => $transfer->fresh()]);
        }

        // receive
        if ($transfer->status !== 'shipped') {
            return response()->json(['message' => __('Only shipped transfers can be received.')], 422);
        }

        DB::transaction(function () use ($transfer) {
            $target = WarehouseStock::firstOrNew([
                'business_id' => $transfer->business_id,
                'warehouse_id' => $transfer->to_warehouse_id,
                'product_id' => $transfer->product_id,
            ]);
            $before = (int) $target->quantity;
            $target->quantity = $before + $transfer->quantity;
            $target->save();

            $moverId = auth()->id();
            StockMovement::create([
                'business_id' => $transfer->business_id,
                'product_id' => $transfer->product_id,
                'warehouse_id' => $transfer->to_warehouse_id,
                // app_users.id is a uuid, so the actor id is stored as-is. Coercing it
                // with ctype_digit() always yielded null and lost the actor entirely.
                'user_id' => $moverId,
                // movement_type is NOT NULL with no default: omitting it raised a
                // not-null violation and rolled back the whole receipt.
                'movement_type' => 'transfer',
                'quantity' => $transfer->quantity,
                'before_quantity' => $before,
                'after_quantity' => $before + $transfer->quantity,
                'reference_type' => 'stock_transfer',
                'reference_id' => $transfer->id,
                'notes' => 'Stock transfer received #' . $transfer->id,
            ]);

            $transfer->update(['status' => 'received']);
        });

        return response()->json(['message' => __('Transfer received.'), 'data' => $transfer->fresh()]);
    }
}
