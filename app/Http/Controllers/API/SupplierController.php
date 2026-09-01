<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    protected SupplierService $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Supplier::query()
            ->with(['ratings', 'contracts', 'performance'])
            ->forBusiness($request->user()->business_id);

        if ($request->has('status') && $request->status === 'active') {
            $query->active();
        }

        $suppliers = $query->latest()->paginate($request->per_page ?? 15);

        return SupplierResource::collection($suppliers);
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['business_id'] = $request->user()->business_id;
        $validated['branch_id'] = $request->user()->branch_id;

        $supplier = $this->supplierService->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully',
            'data' => new SupplierResource($supplier),
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $supplier->load(['ratings', 'contracts', 'performance', 'purchaseOrders']);

        return response()->json([
            'success' => true,
            'data' => new SupplierResource($supplier),
        ]);
    }

    public function update(SupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validated();
        $supplier = $this->supplierService->update($supplier, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => new SupplierResource($supplier),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully',
        ]);
    }

    public function calculatePerformance(Request $request, Supplier $supplier): JsonResponse
    {
        $performance = $this->supplierService->calculatePerformance($supplier);

        return response()->json([
            'success' => true,
            'message' => 'Performance calculated successfully',
            'data' => $performance,
        ]);
    }

    public function topPerformers(Request $request): JsonResponse
    {
        $suppliers = $this->supplierService->getTopPerformers($request->user()->business_id);

        return response()->json([
            'success' => true,
            'data' => SupplierResource::collection($suppliers),
        ]);
    }
}
