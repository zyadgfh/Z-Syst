<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        return PurchaseOrder::query()->latest()->get();
    }

    public function store(Request $request)
    {
        $payload = [
            'supplier_id' => $request->input('supplier_id'),
            'status' => $request->input('status', 'pending'),
            'order_date' => $request->input('order_date', now()->toDateString()),
            'total_amount' => $request->input('total_amount', 0),
        ];

        $purchase = PurchaseOrder::create($payload);

        return response()->json($purchase, 201);
    }
}
