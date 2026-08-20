<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;

class ZSystSaleController extends Controller
{
    public function __construct(private SaleService $saleService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Sale::class);
        
        $filters = $request->only(['search']);
        $perPage = $request->input('per_page', 10);
        $businessId = auth()->user()->business_id;

        $data = $this->saleService->list($filters, $businessId, $perPage);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request)
    {
        $this->authorize('create', Sale::class);

        $sale = $this->saleService->create(
            $request->validated(), 
            auth()->user()->business_id, 
            auth()->id()
        );

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $sale,
        ]);
    }

    public function show($id)
    {
        $sale = clone(Sale::findOrFail($id)); // Ensure it exists before authorization
        $this->authorize('view', $sale);

        $data = $this->saleService->show($id, auth()->user()->business_id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function update(UpdateSaleRequest $request, Sale $sale)
    {
        $this->authorize('update', $sale);

        $this->saleService->update(
            $sale, 
            $request->validated(), 
            auth()->user()->business_id, 
            auth()->id()
        );

        return response()->json([
            'message' => __('Data saved successfully.'),
        ]);
    }

    public function destroy(Sale $sale)
    {
        $this->authorize('delete', $sale);

        $this->saleService->delete(
            $sale, 
            auth()->user()->business_id, 
            auth()->id()
        );

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
