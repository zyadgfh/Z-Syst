<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Warehouse::class);

        $warehouses = Warehouse::where('business_id', $request->user()->business_id)
            ->with('stocks')
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => WarehouseResource::collection($warehouses),
        ]);
    }

    public function store(StoreWarehouseRequest $request)
    {
        $this->authorize('create', Warehouse::class);

        $validated = $request->validated();
        $validated['business_id'] = $request->user()->business_id;

        $warehouse = $this->warehouseService->createWarehouse($validated);

        return response()->json([
            'message' => __('Warehouse created successfully.'),
            'data' => new WarehouseResource($warehouse),
        ], 201);
    }

    public function show(Request $request, Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);

        $warehouse->load('stocks.product');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => new WarehouseResource($warehouse),
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $this->authorize('update', $warehouse);

        $validated = $request->validated();

        $warehouse = $this->warehouseService->updateWarehouse($warehouse, $validated);

        return response()->json([
            'message' => __('Warehouse updated successfully.'),
            'data' => new WarehouseResource($warehouse),
        ]);
    }

    public function destroy(Request $request, Warehouse $warehouse)
    {
        $this->authorize('delete', $warehouse);

        $warehouse->delete();

        return response()->json([
            'message' => __('Warehouse deleted successfully.'),
        ]);
    }

    public function stock(Request $request, Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);

        $warehouse->load('stocks.product');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $warehouse->stocks,
        ]);
    }
}
