<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockTransferController extends Controller
{
    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
        $this->middleware('permission:stock-transfers-create')->only('create', 'store');
        $this->middleware('permission:stock-transfers-read')->only('index', 'show');
        $this->middleware('permission:stock-transfers-update')->only('complete', 'cancel');
        $this->middleware('permission:stock-transfers-delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $transfers = StockTransfer::forBusiness($request->user()->business_id)->with([
            'fromWarehouse:id,name,code',
            'toWarehouse:id,name,code',
            'product:id,name',
            'business:id,companyName',
            'user:id,name',
        ])
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('product', function ($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->warehouse_id, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('from_warehouse_id', $request->warehouse_id)
                        ->orWhere('to_warehouse_id', $request->warehouse_id);
                });
            })
            ->latest()
            ->paginate(10);

        return view('admin.stock-transfers.index', compact('transfers'));
    }

    public function create()
    {
        $businessId = auth()->user()->business_id;
        $warehouses = Warehouse::forBusiness($businessId)->active()->get();

        return view('admin.stock-transfers.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $businessId = $request->user()->business_id;
        abort_if($businessId === null, 403);
        $validated = $request->validate([
            'from_warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('business_id', $businessId)],
            'to_warehouse_id' => ['required', 'different:from_warehouse_id', Rule::exists('warehouses', 'id')->where('business_id', $businessId)],
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', $businessId)],
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);
        $validated['business_id'] = $businessId;
        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'pending';

        try {
            $transfer = $this->warehouseService->createTransfer($validated);

            return response()->json([
                'message' => __('Stock transfer created successfully'),
                'redirect' => route('admin.stock-transfers.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating stock transfer: ').$e->getMessage(),
            ], 500);
        }
    }

    public function show(StockTransfer $transfer)
    {
        $this->assertTransferOwnership($transfer);

        $transfer->load([
            'fromWarehouse',
            'toWarehouse',
            'product',
            'business',
            'user',
        ]);

        return view('admin.stock-transfers.show', compact('transfer'));
    }

    public function destroy(StockTransfer $transfer)
    {
        $this->assertTransferOwnership($transfer);

        if ($transfer->status !== 'pending') {
            return response()->json([
                'message' => __('Cannot delete non-pending transfers'),
            ], 403);
        }

        try {
            $transfer->delete();

            return response()->json([
                'message' => __('Stock transfer deleted successfully'),
                'redirect' => route('admin.stock-transfers.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting stock transfer: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete stock transfer
     */
    public function complete(StockTransfer $transfer)
    {
        $this->assertTransferOwnership($transfer);

        try {
            $transfer = $this->warehouseService->completeTransfer($transfer);

            return response()->json([
                'message' => __('Stock transfer completed successfully'),
                'redirect' => route('admin.stock-transfers.show', $transfer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error completing stock transfer: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel stock transfer
     */
    public function cancel(StockTransfer $transfer)
    {
        $this->assertTransferOwnership($transfer);

        try {
            $transfer = $this->warehouseService->cancelTransfer($transfer);

            return response()->json([
                'message' => __('Stock transfer cancelled successfully'),
                'redirect' => route('admin.stock-transfers.show', $transfer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error cancelling stock transfer: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transfer statistics
     */
    public function statistics(Request $request)
    {
        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'status' => $request->status,
            'warehouse_id' => $request->warehouse_id,
        ];

        $businessId = auth()->user()->business_id;
        $statistics = $this->warehouseService->getTransferStatistics($businessId, $filters);

        return response()->json($statistics);
    }

    /**
     * Get stock distribution for a product
     */
    public function stockDistribution(Request $request)
    {
        $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', $request->user()->business_id)],
        ]);

        $businessId = auth()->user()->business_id;
        $distribution = $this->warehouseService->getStockDistribution($businessId, $request->product_id);

        return response()->json($distribution);
    }
    private function assertTransferOwnership(StockTransfer $transfer): void
    {
        $businessId = auth()->user()->business_id;
        abort_unless($businessId !== null && (string) $transfer->business_id === (string) $businessId, 404);
    }

}
