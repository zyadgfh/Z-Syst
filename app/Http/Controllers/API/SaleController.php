<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    protected $service;

    public function __construct(SaleService $service)
    {
        $this->service = $service;
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->service->createSale($request->validated());

        return response()->json(['data' => new SaleResource($sale)], 201);
    }
}
