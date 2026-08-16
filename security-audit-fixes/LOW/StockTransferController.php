<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
{
    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = StockTransfer::forBusiness()->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('fromWarehouse', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('toWarehouse', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('warehouse_id')) {
            $warehouseId = (int) $request->input('warehouse_id');
            $query->where(function ($q) use ($warehouseId) {
                $q->where('from_warehouse_id', $warehouseId)
                  ->orWhere('to_warehouse_id', $warehouseId);
            });
        }

        $transfers = $query->paginate($perPage);
        $warehouses = $this->warehouseService->getWarehousesForBusiness();

        return view('admin.stock-transfers.index', compact('transfers', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id|different:from_warehouse_id',
            'notes'             => 'nullable|string|max:1000',
            'items'             => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'  => 'required|integer|min:1',
        ]);

        $validated['business_id'] = Auth::user()->business_id;
        $validated['reference_number'] = $this->generateReferenceNumber();
        $validated['status'] = 'pending';
        $validated['created_by'] = Auth::id();

        $transfer = StockTransfer::create($validated);

        foreach ($request->input('items') as $item) {
            $transfer->items()->create($item);
        }

        return redirect()->route('admin.stock-transfers.show', $transfer->id)
                         ->with('success', 'Stock transfer created successfully.');
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['items.product', 'fromWarehouse', 'toWarehouse', 'creator']);

        return view('admin.stock-transfers.show', ['transfer' => $stockTransfer]);
    }

    public function complete(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'pending') {
            return back()->with('error', 'Only pending transfers can be completed.');
        }

        $stockTransfer->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'completed_by' => Auth::id(),
        ]);

        return redirect()->route('admin.stock-transfers.show', $stockTransfer->id)
                         ->with('success', 'Stock transfer completed.');
    }

    public function cancel(StockTransfer $stockTransfer)
    {
        if (!in_array($stockTransfer->status, ['pending'])) {
            return back()->with('error', 'Only pending transfers can be cancelled.');
        }

        $stockTransfer->update([
            'status'      => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
        ]);

        return redirect()->route('admin.stock-transfers.show', $stockTransfer->id)
                         ->with('success', 'Stock transfer cancelled.');
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'pending') {
            return back()->with('error', 'Only pending transfers can be deleted.');
        }

        $stockTransfer->delete();

        return redirect()->route('admin.stock-transfers.index')
                         ->with('success', 'Stock transfer deleted successfully.');
    }

    private function generateReferenceNumber(): string
    {
        return 'ST-' . now()->format('Ymd') . '-' . str_pad(StockTransfer::max('id') + 1, 5, '0', STR_PAD_LEFT);
    }
}
