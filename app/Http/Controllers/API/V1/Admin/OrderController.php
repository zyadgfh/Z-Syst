<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 25);
        $orders = Order::with('items')->paginate($perPage);
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
        ]);

        $order = Order::create(['uuid' => (string) Str::uuid(), 'branch_id' => $data['branch_id'] ?? null]);

        foreach ($data['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'company_id' => $order->company_id,
                'product_id' => $item['product_id'] ?? null,
                'drug_id' => $item['drug_id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
            ]);
        }

        return response()->json($order->load('items'), 201);
    }
}
