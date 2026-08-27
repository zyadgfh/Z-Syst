<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\StockTransfer;
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

    // ── Stock Transfer Endpoints ──

    public function transfers(Request $request)
    {
        $businessId = $request->user()->business_id;
        $perPage = $request->input('per_page', 15);

        $query = StockTransfer::where('business_id', $businessId)
            ->with(['fromWarehouse:id,name,code', 'toWarehouse:id,name,code', 'product:id,productName,productCode'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('from_warehouse_id', $request->input('warehouse_id'))
                ->orWhere('to_warehouse_id', $request->input('warehouse_id'))
            ));

        $data = $query->latest()->paginate($perPage);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function storeTransfer(StoreStockTransferRequest $request)
    {
        $validated = $request->validated();
        $validated['business_id'] = $request->user()->business_id;
        $validated['user_id'] = $request->user()->id;

        $transfer = $this->warehouseService->createTransfer($validated);

        return response()->json([
            'message' => __('Stock transfer created successfully.'),
            'data' => $transfer->load(['fromWarehouse:id,name', 'toWarehouse:id,name', 'product:id,productName']),
        ], 201);
    }

    public function completeTransfer(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $transfer = $this->warehouseService->completeTransfer($stockTransfer);

        return response()->json([
            'message' => __('Stock transfer completed successfully.'),
            'data' => $transfer,
        ]);
    }

    public function cancelTransfer(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $transfer = $this->warehouseService->cancelTransfer($stockTransfer);

        return response()->json([
            'message' => __('Stock transfer cancelled successfully.'),
            'data' => $transfer,
        ]);
    }

    public function transferStatistics(Request $request)
    {
        $businessId = $request->user()->business_id;
        $stats = $this->warehouseService->getTransferStatistics($businessId, $request->only(['date_from', 'date_to', 'status', 'warehouse_id']));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $stats,
        ]);
    }
}
