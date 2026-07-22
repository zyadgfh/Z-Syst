<?php

declare(strict_types=1);

namespace App\Modules\Products\Infrastructure\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Modules\Products\Domain\DTOs\CreateProductDTO;
use App\Modules\Products\Domain\DTOs\UpdateProductDTO;
use App\Modules\Products\Application\Services\ProductService;
use App\Modules\Products\Infrastructure\Requests\StoreProductRequest;
use App\Modules\Products\Infrastructure\Requests\UpdateProductRequest;
use App\Modules\Products\Infrastructure\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Product Controller
 *
 * Thin controller — only orchestrates request/response.
 * All business logic is delegated to ProductService.
 */
class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display a paginated listing of products.
     *
     * @queryParam search string Search by name, barcode, SKU, or generic name.
     * @queryParam category_id int Filter by category.
     * @queryParam manufacturer_id int Filter by manufacturer.
     * @queryParam is_active bool Filter by active status.
     * @queryParam per_page int Items per page (default: 15).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = array_merge(
            $request->only(['search', 'category_id', 'manufacturer_id', 'is_active', 'per_page']),
            ['company_id' => $request->user()->company_id]
        );

        $products = $this->productService->list($filters);

        return response()->json([
            'success' => true,
            'message' => __('products.fetched'),
            'data' => ProductResource::collection($products->items()),
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
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;
        $data['created_by'] = $request->user()->id;

        $dto = CreateProductDTO::fromRequest($data);
        $product = $this->productService->create($dto);

        return response()->json([
            'success' => true,
            'message' => __('products.created'),
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->loadMissing(['category', 'manufacturer', 'stocks', 'priceHistory']);

        return response()->json([
            'success' => true,
            'message' => __('products.fetched'),
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $dto = UpdateProductDTO::fromRequest($data);
        $product = $this->productService->update($product, $dto);

        return response()->json([
            'success' => true,
            'message' => __('products.updated'),
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Remove the specified product (soft-delete).
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json([
            'success' => true,
            'message' => __('products.deleted'),
        ]);
    }

    /**
     * Search products for autocomplete.
     *
     * @queryParam q string required The search query (min 2 chars).
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $results = $this->productService->search(
            $request->get('q'),
            $request->user()->company_id
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Find product by barcode.
     */
    public function byBarcode(string $barcode): JsonResponse
    {
        $product = $this->productService->findByBarcode($barcode);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('products.not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('products.fetched'),
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Get stock overview for a specific product with batch details.
     *
     * Shows all batches: batch_number, expiry_date, quantity, etc.
     */
    public function stock(Product $product): JsonResponse
    {
        $branchId = request()->user()->branch_id ?? request('branch_id');
        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'branch_id is required',
            ], 400);
        }

        $overview = $this->productService->getProductStock($product->id, $branchId);

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    /**
     * Get products expiring soon.
     *
     * @queryParam days int Number of days to look ahead (default: 30).
     */
    public function expiring(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $companyId = $request->user()->company_id;

        $products = $this->productService->getExpiringProducts($companyId, $days);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
            ],
        ]);
    }

    /**
     * Get low stock products (below reorder point).
     */
    public function lowStock(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $products = $this->productService->getLowStockProducts($companyId);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
            ],
        ]);
    }
}

