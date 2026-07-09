<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $orders = Order::forCompany($request->user()->company_id)
            ->with(['items', 'branch'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required_without:items.*.drug_id|exists:products,id',
            'items.*.drug_id' => 'required_without:items.*.product_id|exists:drugs,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $data = $validator->validated();

        $order = Order::create([
            'company_id' => $request->user()->company_id,
            'branch_id' => $data['branch_id'],
            'uuid' => (string) Str::uuid(),
            'status' => 'pending',
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'total' => collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['price']),
            'created_by' => $request->user()->id,
        ]);

        foreach ($data['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'company_id' => $order->company_id,
                'product_id' => $item['product_id'] ?? null,
                'drug_id' => $item['drug_id'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['quantity'] * $item['price'],
            ]);
        }

        return $this->created($order->load(['items', 'branch']));
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->company_id !== $request->user()->company_id) {
            return $this->forbidden();
        }

        return $this->success($order->load(['items', 'branch']));
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        if ($order->company_id !== $request->user()->company_id) {
            return $this->forbidden();
        }

        if (! in_array($order->status, ['pending'])) {
            return $this->error('Cannot modify a processed order', 422);
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,processing,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $order->update($validator->validated());

        return $this->success($order->load(['items', 'branch']));
    }

    public function destroy(Request $request, Order $order): JsonResponse
    {
        if ($order->company_id !== $request->user()->company_id) {
            return $this->forbidden();
        }

        if (! in_array($order->status, ['pending'])) {
            return $this->error('Cannot delete a processed order', 422);
        }

        $order->delete();

        return $this->success(null, 'Order deleted successfully');
    }
}
