<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductStockRequest;
use App\Http\Requests\Admin\UpdateProductStockRequest;
use App\Http\Resources\ProductStockResource;
use App\Models\ProductStock;
use Illuminate\Http\Request;

class ProductStockController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductStock::query()
            ->with(['product', 'branch'])
            ->where('company_id', $request->user()->company_id ?? app('tenant.company_id'));

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->query('product_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->query('branch_id'));
        }

        return ProductStockResource::collection($query->latest()->paginate($request->query('per_page', 25)))->response();
    }

    public function store(StoreProductStockRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id ?? app('tenant.company_id');

        $stock = ProductStock::create($data);

        return (new ProductStockResource($stock))->response()->setStatusCode(201);
    }

    public function show(ProductStock $productStock)
    {
        return (new ProductStockResource($productStock->load(['product', 'branch'])))->response();
    }

    public function update(UpdateProductStockRequest $request, ProductStock $productStock)
    {
        $productStock->update($request->validated());

        return (new ProductStockResource($productStock->fresh()->load(['product', 'branch'])))->response();
    }

    public function destroy(ProductStock $productStock)
    {
        $productStock->delete();

        return response()->json(['success' => true, 'message' => 'Product stock deleted successfully.']);
    }
}
