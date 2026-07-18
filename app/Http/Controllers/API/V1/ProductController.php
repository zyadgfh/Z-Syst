<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductSearchService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Product Controller for Pharmacy Management
 * 
 * Handles all product-related API endpoints:
 * - CRUD operations
 * - Advanced search
 * - Barcode scanning
 * - Drug interaction checks
 */
class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected ProductSearchService $searchService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'barcode', 'category_id', 'manufacturer_id',
            'dosage_form', 'controlled_substance', 'prescription_required',
            'low_stock', 'expired', 'expiring_soon', 'expiring_soon_days',
            'per_page'
        ]);

        $products = $this->productService->search($filters);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'from' => $products->firstItem(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'to' => $products->lastItem(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Get product by barcode.
     */
    public function byBarcode(string $barcode): JsonResponse
    {
        $product = $this->searchService->searchByBarcode($barcode);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'المنتج غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Store a new product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'generic_name' => 'required|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'product_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode',
            'product_code' => 'nullable|string|unique:products,product_code',
            'category_id' => 'nullable|exists:categories,id',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'prescription_required' => 'boolean',
            'controlled_substance_schedule' => 'nullable|in:1,2,3,4,5',
            'sales_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'batch_no' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'qty' => 'nullable|numeric|min:0',
        ]);

        $product = $this->productService->create($validated);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'تم إنشاء المنتج بنجاح',
        ], 201);
    }

    /**
     * Display a single product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load([
            'category',
            'manufacturer',
            'tax',
            'variants',
            'priceHistory' => function ($q) {
                $q->latest()->limit(10);
            },
            'stocks' => function ($q) {
                $q->where('productStock', '>', 0)
                    ->orderBy('expire_date', 'asc');
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update a product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'generic_name' => 'sometimes|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'product_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'product_code' => 'nullable|string|unique:products,product_code,' . $product->id,
            'category_id' => 'nullable|exists:categories,id',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'prescription_required' => 'boolean',
            'controlled_substance_schedule' => 'nullable|in:1,2,3,4,5',
            'sales_price' => 'sometimes|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $product = $this->productService->update($product, $validated);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'تم تحديث المنتج بنجاح',
        ]);
    }

    /**
     * Delete a product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنتج بنجاح',
        ]);
    }

    /**
     * Search products (autocomplete).
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $results = $this->searchService->getSuggestions($validated['q']);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Check drug interactions for given products.
     */
    public function checkInteractions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => 'required|array|min:2',
            'product_ids.*' => 'exists:products,id',
        ]);

        $interactions = $this->productService->checkDrugInteractions($validated['product_ids']);

        return response()->json([
            'success' => true,
            'data' => $interactions,
        ]);
    }

    /**
     * Get product suggestions for autocomplete.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1',
        ]);

        $results = $this->searchService->getSuggestions($validated['q']);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}