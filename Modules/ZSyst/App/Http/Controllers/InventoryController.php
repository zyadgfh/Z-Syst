<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ZSyst\App\Models\InventoryMovement;

class InventoryController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => InventoryMovement::latest()->take(20)->get(),
            'count' => InventoryMovement::count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'drug_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:in,out,adjustment'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $movement = InventoryMovement::create($data);

        return response()->json([
            'message' => 'Inventory movement recorded',
            'data' => $movement,
        ], 201);
    }
}
