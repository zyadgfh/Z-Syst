<?php

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Requests\StoreSaleRequest;
use App\Modules\Sales\Requests\UpdateSaleRequest;
use App\Modules\Sales\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function index(Request $request): JsonResponse
    {
        $sales = $this->saleService->getSales($request->all(), auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $sales,
        ]);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->createSale($request->validated(), auth()->user()->business_id, auth()->id());
        return response()->json([
            'success' => true,
            'message' => 'Sale created successfully',
            'data' => $sale,
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $sale = $this->saleService->getSale($id, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    public function update(UpdateSaleRequest $request, $id): JsonResponse
    {
        $sale = $this->saleService->updateSale($id, $request->validated(), auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully',
            'data' => $sale,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->saleService->deleteSale($id, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'message' => 'Sale deleted successfully',
        ]);
    }

    public function calculateProfitLoss($id): JsonResponse
    {
        $profitLoss = $this->saleService->calculateProfitLoss($id, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $profitLoss,
        ]);
    }
}
