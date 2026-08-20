<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchaseService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Purchase::class);
        
        $filters = $request->only(['search']);
        $perPage = $request->input('per_page', 10);
        $businessId = auth()->user()->business_id;

        $data = $this->purchaseService->list($filters, $businessId, $perPage);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        $this->authorize('create', Purchase::class);

        $purchase = $this->purchaseService->create(
            $request->validated(), 
            auth()->user()->business_id, 
            auth()->id()
        );

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $purchase,
        ]);
    }

    public function show($id)
    {
        $purchase = clone(Purchase::findOrFail($id)); // Ensure it exists before authorization
        $this->authorize('view', $purchase);

        $data = $this->purchaseService->show($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        $this->authorize('update', $purchase);

        $this->purchaseService->update(
            $purchase, 
            $request->validated(), 
            auth()->user()->business_id, 
            auth()->id()
        );

        return response()->json([
            'message' => __('Data saved successfully.'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        $this->authorize('delete', $purchase);

        $this->purchaseService->delete(
            $purchase, 
            auth()->user()->business_id, 
            auth()->id()
        );

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
