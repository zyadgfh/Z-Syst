<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseStockService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    protected WarehouseStockService $stockService;

    public function __construct(WarehouseStockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
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
        $validated = $request->validated();

        $validated['business_id'] = $request->user()->business_id;

        $warehouse = $this->stockService->createWarehouse($validated);

        return response()->json([
            'message' => __('Warehouse created successfully.'),
            'data' => new WarehouseResource($warehouse),
        ], 201);
    }

    public function show(Request $request, Warehouse $warehouse)
    {
        $this->authorizeWarehouseAccess($request, $warehouse);

        $warehouse->load('stocks.product');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => new WarehouseResource($warehouse),
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $this->authorizeWarehouseAccess($request, $warehouse);

        $validated = $request->validated();

        $warehouse = $this->stockService->updateWarehouse($warehouse, $validated);

        return response()->json([
            'message' => __('Warehouse updated successfully.'),
            'data' => new WarehouseResource($warehouse),
        ]);
    }

    public function destroy(Request $request, Warehouse $warehouse)
    {
        $this->authorizeWarehouseAccess($request, $warehouse);

        $warehouse->delete();

        return response()->json([
            'message' => __('Warehouse deleted successfully.'),
        ]);
    }

    public function stock(Request $request, Warehouse $warehouse)
    {
        $this->authorizeWarehouseAccess($request, $warehouse);

        $warehouse->load('stocks.product');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $warehouse->stocks,
        ]);
    }

    protected function authorizeWarehouseAccess(Request $request, Warehouse $warehouse): void
    {
        if ($warehouse->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }
    }
}
