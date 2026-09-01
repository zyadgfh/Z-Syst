<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = Warehouse::forBusiness()->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $warehouses = $query->paginate($perPage);

        return view('admin.warehouses.index', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'location' => 'nullable|string|max:500',
            'phone'    => 'nullable|string|max:50',
            'email'    => 'nullable|email|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['business_id'] = Auth::user()->business_id;

        $warehouse = Warehouse::create($validated);

        return redirect()->route('admin.warehouses.index')
                         ->with('success', 'Warehouse created successfully.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'location' => 'nullable|string|max:500',
            'phone'    => 'nullable|string|max:50',
            'email'    => 'nullable|email|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        // Explicitly prevent business_id overwrite
        unset($validated['business_id']);

        $warehouse->update($validated);

        return redirect()->route('admin.warehouses.index')
                         ->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')
                         ->with('success', 'Warehouse deleted successfully.');
    }

    public function setDefault(Warehouse $warehouse)
    {
        // Unset other defaults for this business
        Warehouse::forBusiness()
                 ->where('is_default', true)
                 ->update(['is_default' => false]);

        $warehouse->update(['is_default' => true]);

        return redirect()->route('admin.warehouses.index')
                         ->with('success', 'Default warehouse updated.');
    }
}
