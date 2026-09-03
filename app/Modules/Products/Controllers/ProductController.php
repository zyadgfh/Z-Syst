<?php

namespace App\Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Requests\StoreProductRequest;
use App\Modules\Products\Requests\UpdateProductRequest;
use App\Modules\Products\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getProducts($request->all(), auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated(), auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $product = $this->productService->getProduct($id, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function update(UpdateProductRequest $request, $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated(), auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->productService->deleteProduct($id, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    public function getStock(Request $request): JsonResponse
    {
        $stock = $this->productService->getProductStock($request->product_id, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $stock,
        ]);
    }

    public function updateStock(Request $request): JsonResponse
    {
        $this->productService->updateStock($request->product_id, $request->all(), auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
        ]);
    }
}
