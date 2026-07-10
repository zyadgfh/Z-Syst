<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ZSyst\App\Models\InventoryItem;

class InventoryItemController
{
    public function index(): JsonResponse
    {
        return response()->json(InventoryItem::latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'drug_id' => ['required', 'integer', 'exists:drugs,id'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
            'unit_cost' => ['nullable', 'numeric'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $item = InventoryItem::create($data);

        return response()->json([
            'message' => 'Inventory item recorded',
            'data' => $item,
        ], 201);
    }
}
