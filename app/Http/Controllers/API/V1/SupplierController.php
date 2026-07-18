<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Supplier Controller for Pharmacy Management
 */
class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('contact_person', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $suppliers = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $suppliers->items(),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    /**
     * Store a new supplier.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $validated['company_id'] = app('tenant.company_id');
        $validated['created_by'] = auth()->id();

        $supplier = Supplier::create($validated);

        return response()->json([
            'success' => true,
            'data' => $supplier,
            'message' => 'تم إنشاء المورد بنجاح',
        ], 201);
    }

    /**
     * Display a single supplier.
     */
    public function show(Supplier $supplier): JsonResponse
    {
        $supplier->load([
            'purchaseOrders' => function ($q) {
                $q->latest()->limit(10);
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    /**
     * Update a supplier.
     */
    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $supplier->update($validated);

        return response()->json([
            'success' => true,
            'data' => $supplier,
            'message' => 'تم تحديث المورد بنجاح',
        ]);
    }

    /**
     * Delete a supplier.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المورد بنجاح',
        ]);
    }
}