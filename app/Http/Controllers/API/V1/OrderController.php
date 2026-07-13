<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::forCompany($request->user()->company_id)
            ->with(['branch:id,name', 'items.product:id,productName'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->branch_id, fn($q, $v) => $q->where('branch_id', $v))
            ->when($request->search, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('customer_name', 'like', "%{$v}%")
                        ->orWhere('customer_phone', 'like', "%{$v}%")
                        ->orWhere('uuid', 'like', "%{$v}%");
                });
            })
            ->when($request->date_from, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();
        $total = collect($data['items'])->sum(fn($item) => $item['quantity'] * $item['price']);

        $order = Order::create([
            'company_id' => $request->user()->company_id,
            'branch_id' => $data['branch_id'],
            'uuid' => (string) Str::uuid(),
            'status' => 'pending',
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'total' => $total,
            'created_by' => $request->user()->id,
        ]);

        foreach ($data['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'company_id' => $order->company_id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['quantity'] * $item['price'],
            ]);
        }

        return response()->json($order->load(['items.product:id,productName']), 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($order->load(['branch:id,name', 'items.product:id,productName']));
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        if ($order->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($order->status, ['pending'])) {
            return response()->json(['message' => 'Cannot modify a processed order'], 422);
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,processing,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $order->update($validator->validated());

        return response()->json($order->fresh());
    }

    public function destroy(Request $request, Order $order): JsonResponse
    {
        if ($order->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($order->status, ['pending'])) {
            return response()->json(['message' => 'Cannot delete a processed order'], 422);
        }

        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}