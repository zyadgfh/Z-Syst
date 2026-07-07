<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductStockRequest;
use App\Http\Requests\Admin\UpdateProductStockRequest;
use App\Http\Resources\ProductStockResource;
use App\Models\ProductStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductStockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $stocks = ProductStock::query()
            ->with(['product', 'branch'])
            ->where('company_id', $request->user()->company_id)
            ->when($request->has('branch_id'), fn ($query) => $query->where('branch_id', $request->query('branch_id')))
            ->when($request->has('product_id'), fn ($query) => $query->where('product_id', $request->query('product_id')))
            ->paginate(25);

        return response()->json(ProductStockResource::collection($stocks));
    }

    public function store(StoreProductStockRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;

        $stock = ProductStock::create($data);

        return response()->json(['message' => 'Product stock created successfully.', 'data' => new ProductStockResource($stock)], 201);
    }

    public function show(ProductStock $productStock): JsonResponse
    {
        $productStock->load(['product', 'branch']);

        return response()->json(new ProductStockResource($productStock));
    }

    public function update(UpdateProductStockRequest $request, ProductStock $productStock): JsonResponse
    {
        $productStock->update($request->validated());

        return response()->json(['message' => 'Product stock updated successfully.', 'data' => new ProductStockResource($productStock)]);
    }

    public function destroy(ProductStock $productStock): JsonResponse
    {
        $productStock->delete();

        return response()->json(['message' => 'Product stock deleted successfully.']);
    }
}
