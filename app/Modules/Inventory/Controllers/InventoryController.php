<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Requests\CreateStockMovementRequest;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request): JsonResponse
    {
        $inventory = $this->inventoryService->getInventory($request->all(), auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    public function getLowStock(Request $request): JsonResponse
    {
        $lowStock = $this->inventoryService->getLowStock(auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $lowStock,
        ]);
    }

    public function getExpiringBatches(Request $request): JsonResponse
    {
        $expiring = $this->inventoryService->getExpiringBatches(auth()->user()->businessId, $request->days ?? 30);
        return response()->json([
            'success' => true,
            'data' => $expiring,
        ]);
    }

    public function createStockMovement(CreateStockMovementRequest $request): JsonResponse
    {
        $movement = $this->inventoryService->createStockMovement($request->validated(), auth()->user()->business_id, auth()->id());
        return response()->json([
            'success' => true,
            'message' => 'Stock movement created successfully',
            'data' => $movement,
        ], 201);
    }

    public function getStockMovements(Request $request): JsonResponse
    {
        $movements = $this->inventoryService->getStockMovements($request->all(), auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }
}
