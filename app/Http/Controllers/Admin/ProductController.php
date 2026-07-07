<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['category', 'company'])
            ->where('company_id', $request->user()->company_id)
            ->when($request->has('category_id'), fn ($query) => $query->where('product_category_id', $request->query('category_id')))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)))
            ->paginate(25);

        return response()->json(ProductResource::collection($products));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;
        $data['created_by'] = $request->user()->id;

        $product = Product::create($data);

        return response()->json(['message' => 'Product created successfully.', 'data' => new ProductResource($product)], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'company', 'stocks']);

        return response()->json(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $product->update($data);

        return response()->json(['message' => 'Product updated successfully.', 'data' => new ProductResource($product)]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
