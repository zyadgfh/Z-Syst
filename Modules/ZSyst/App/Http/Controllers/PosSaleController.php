<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ZSyst\App\Models\PosSale;

class PosSaleController
{
    public function index(): JsonResponse
    {
        return response()->json(PosSale::latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'subtotal' => ['nullable', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'total_amount' => ['nullable', 'numeric'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $sale = PosSale::create($data + ['status' => $data['status'] ?? 'completed']);

        return response()->json($sale, 201);
    }
}
