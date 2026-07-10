<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ZSyst\App\Models\ProcurementOrder;

class ProcurementController
{
    public function index(): JsonResponse
    {
        return response()->json(ProcurementOrder::latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'expected_delivery_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'total_amount' => ['nullable', 'numeric'],
        ]);

        $order = ProcurementOrder::create($data + ['status' => $data['status'] ?? 'draft']);

        return response()->json($order, 201);
    }
}
