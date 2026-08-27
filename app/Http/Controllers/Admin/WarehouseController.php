<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
        $this->middleware('permission:warehouses-create')->only('create', 'store');
        $this->middleware('permission:warehouses-read')->only('index', 'show');
        $this->middleware('permission:warehouses-update')->only('edit', 'update', 'setDefault');
        $this->middleware('permission:warehouses-delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $warehouses = Warehouse::with(['business:id,companyName'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('admin.warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('admin.warehouses.create');
    }

    public function store(StoreWarehouseRequest $request)
    {
        try {
            $warehouse = $this->warehouseService->createWarehouse($request->validated());

            return response()->json([
                'message' => __('Warehouse created successfully'),
                'redirect' => route('admin.warehouses.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating warehouse: ').$e->getMessage(),
            ], 500);
        }
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load(['stocks.product', 'transfersFrom', 'transfersTo']);
        $statistics = $this->warehouseService->getWarehouseStatistics($warehouse->business_id, $warehouse->id);

        return view('admin.warehouses.show', compact('warehouse', 'statistics'));
    }

    public function edit(Warehouse $warehouse)
    {
        return view('admin.warehouses.edit', compact('warehouse'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        try {
            $warehouse = $this->warehouseService->updateWarehouse($warehouse, $request->validated());

            return response()->json([
                'message' => __('Warehouse updated successfully'),
                'redirect' => route('admin.warehouses.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error updating warehouse: ').$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Warehouse $warehouse)
    {
        try {
            $this->warehouseService->deleteWarehouse($warehouse);

            return response()->json([
                'message' => __('Warehouse deleted successfully'),
                'redirect' => route('admin.warehouses.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting warehouse: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set warehouse as default
     */
    public function setDefault(Warehouse $warehouse)
    {
        try {
            $warehouse = $this->warehouseService->setDefaultWarehouse($warehouse);

            return response()->json([
                'message' => __('Warehouse set as default successfully'),
                'redirect' => route('admin.warehouses.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error setting default warehouse: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add stock to warehouse
     */
    public function addStock(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $stock = $this->warehouseService->addStock(
                $warehouse->id,
                $request->product_id,
                $request->quantity,
                $warehouse->business_id
            );

            return response()->json([
                'message' => __('Stock added successfully'),
                'stock' => $stock,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error adding stock: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove stock from warehouse
     */
    public function removeStock(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->warehouseService->removeStock(
                $warehouse->id,
                $request->product_id,
                $request->quantity
            );

            return response()->json([
                'message' => __('Stock removed successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error removing stock: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get warehouse statistics
     */
    public function statistics(Request $request)
    {
        $businessId = $request->business_id ?? auth()->user()->business_id;
        $warehouseId = $request->warehouse_id ?? null;

        $statistics = $this->warehouseService->getWarehouseStatistics($businessId, $warehouseId);

        return response()->json($statistics);
    }
}
