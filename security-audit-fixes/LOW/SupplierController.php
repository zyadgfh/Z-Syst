<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = Supplier::forBusiness()->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $suppliers = $query->paginate($perPage);

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function store(SupplierRequest $request)
    {
        $validated = $request->validated();
        $validated['business_id'] = Auth::user()->business_id;

        $supplier = $this->supplierService->create($validated);

        return redirect()->route('admin.suppliers.show', $supplier->id)
                         ->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('invoices', 'products');

        return view('admin.suppliers.show', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $validated = $request->validated();

        // Explicitly prevent business_id overwrite
        unset($validated['business_id']);

        $this->supplierService->update($supplier, $validated);

        return redirect()->route('admin.suppliers.show', $supplier->id)
                         ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $this->supplierService->delete($supplier);

        return redirect()->route('admin.suppliers.index')
                         ->with('success', 'Supplier deleted successfully.');
    }
}
