<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductCategoryRequest;
use App\Http\Requests\Admin\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = ProductCategory::query()
            ->with('children')
            ->where('company_id', $request->user()->company_id)
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)))
            ->paginate(25);

        return response()->json(ProductCategoryResource::collection($categories));
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;
        $data['created_by'] = $request->user()->id;

        $category = ProductCategory::create($data);

        return response()->json(['message' => 'Product category created successfully.', 'data' => new ProductCategoryResource($category)], 201);
    }

    public function show(ProductCategory $productCategory): JsonResponse
    {
        $productCategory->load('children', 'parent');

        return response()->json(new ProductCategoryResource($productCategory));
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $productCategory->update($data);

        return response()->json(['message' => 'Product category updated successfully.', 'data' => new ProductCategoryResource($productCategory)]);
    }

    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $productCategory->delete();

        return response()->json(['message' => 'Product category deleted successfully.']);
    }
}
