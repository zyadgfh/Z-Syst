<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Items Management API v2
 *
 * RESTful API with proper versioning, pagination, field selection, and filtering.
 * All endpoints require authentication via Bearer token.
 */
#[OA\Tag(name: 'Items V2', description: 'Items management API v2 — CRUD, search, stock movements, KPIs')]
class ItemController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * List items with pagination, filtering, sorting, and field selection.
     */
    #[OA\Get(
        path: '/api/v2/items',
        summary: 'List items',
        description: 'Returns a paginated list of items with optional filtering, sorting, and field selection.',
        tags: ['Items V2'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100), description: 'Items per page'),
            new OA\Parameter(name: 'fields', in: 'query', schema: new OA\Schema(type: 'string', example: 'id,productName,barcode,sales_price'), description: 'Comma-separated fields to return'),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Free-text search across name, barcode, SKU, scientific_name'),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filter by category ID'),
            new OA\Parameter(name: 'manufacturer_id', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filter by manufacturer ID'),
            new OA\Parameter(name: 'brand_id', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filter by brand ID'),
            new OA\Parameter(name: 'active', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1]), description: 'Filter by active status'),
            new OA\Parameter(name: 'stock_status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['in_stock', 'low_stock', 'out_of_stock']), description: 'Filter by stock status'),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', example: '-sales_price'), description: 'Sort field (prefix with - for descending)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated items list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;
        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = Product::where('business_id', $businessId)
            ->with(['category:id,categoryName', 'manufacturer:id,name', 'unit:id,unitName']);

        // ── Search ──
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('productName', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('productCode', 'like', "%{$term}%")
                    ->orWhere('scientific_name', 'like', "%{$term}%");
            });
        }

        // ── Filters ──
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('manufacturer_id')) {
            $query->where('manufacturer_id', $request->input('manufacturer_id'));
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }
        if ($request->has('active')) {
            $query->where('active', (bool) $request->input('active'));
        }
        if ($request->filled('stock_status')) {
            $query->withSum('stocks', 'productStock');
            match ($request->input('stock_status')) {
                'in_stock' => $query->having('stocks_sum_productstock', '>', 0),
                'low_stock' => $query->having('stocks_sum_productstock', '>', 0)
                    ->having('stocks_sum_productstock', '<=', 'alert_qty'),
                'out_of_stock' => $query->having('stocks_sum_productstock', '<=', 0),
                default => null,
            };
        }
        if ($request->filled('tax_type')) {
            $query->where('tax_type', $request->input('tax_type'));
        }
        if ($request->has('prescription_required')) {
            $query->where('prescription_required', (bool) $request->input('prescription_required'));
        }

        // ── Sorting ──
        $sortField = ltrim($request->input('sort', 'id'), '-');
        $sortDirection = str_starts_with($request->input('sort', 'id'), '-') ? 'desc' : 'asc';
        $allowedSorts = ['id', 'productName', 'barcode', 'sku', 'sales_price', 'purchase_with_tax', 'created_at', 'active'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // ── Field Selection ──
        $requestedFields = $request->has('fields')
            ? array_map('trim', explode(',', $request->input('fields')))
            : ['id', 'productName', 'barcode', 'sku', 'sales_price', 'active'];

        // Always include id for relationship resolution
        $requestedFields = array_unique(array_merge($requestedFields, ['id']));
        $query->select($requestedFields);

        $items = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
            'links' => [
                'self' => $items->url($items->currentPage()),
                'first' => $items->url(1),
                'last' => $items->url($items->lastPage()),
                'next' => $items->nextPageUrl(),
                'prev' => $items->previousPageUrl(),
            ],
        ]);
    }

    /**
     * Show a single item with full details.
     */
    #[OA\Get(
        path: '/api/v2/items/{id}',
        summary: 'Get item details',
        description: 'Returns full item details including computed stock status and total stock.',
        tags: ['Items V2'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Item ID'),
            new OA\Parameter(name: 'fields', in: 'query', schema: new OA\Schema(type: 'string', example: 'id,productName,sales_price'), description: 'Comma-separated fields to return'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Item details'),
            new OA\Response(response: 404, description: 'Item not found'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $businessId = $request->user()->business_id;

        $product = Product::where('id', $id)
            ->where('business_id', $businessId)
            ->with([
                'category:id,categoryName',
                'manufacturer:id,name',
                'unit:id,unitName',
                'tax:id,rate,name',
                'stocks:id,product_id,batch_no,productStock,expire_date,purchase_price',
            ])
            ->firstOrFail();

        // Field selection
        if ($request->has('fields')) {
            $fields = array_map('trim', explode(',', $request->input('fields')));
            $product = collect($product->toArray())->only($fields)->all();
        }

        // Add computed fields
        $totalStock = $product->stocks->sum('productStock') ?? 0;

        $data = is_array($product) ? $product : $product->toArray();
        $data['total_stock'] = $totalStock;
        $data['stock_status'] = match (true) {
            $totalStock <= 0 => 'out_of_stock',
            $totalStock <= ($data['alert_qty'] ?? 0) => 'low_stock',
            default => 'in_stock',
        };

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Create a new item.
     */
    #[OA\Post(
        path: '/api/v2/items',
        summary: 'Create item',
        description: 'Create a new product/item with optional initial stock batch.',
        tags: ['Items V2'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['productName', 'category_id', 'unit_id', 'purchase_without_tax', 'purchase_with_tax', 'sales_price'],
                    properties: [
                        new OA\Property(property: 'productName', type: 'string', example: 'Amoxicillin 500mg'),
                        new OA\Property(property: 'category_id', type: 'integer'),
                        new OA\Property(property: 'unit_id', type: 'integer'),
                        new OA\Property(property: 'manufacturer_id', type: 'integer', nullable: true),
                        new OA\Property(property: 'barcode', type: 'string', nullable: true),
                        new OA\Property(property: 'sku', type: 'string', nullable: true),
                        new OA\Property(property: 'purchase_without_tax', type: 'number', format: 'float'),
                        new OA\Property(property: 'purchase_with_tax', type: 'number', format: 'float'),
                        new OA\Property(property: 'sales_price', type: 'number', format: 'float'),
                        new OA\Property(property: 'wholesale_price', type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'alert_qty', type: 'integer', nullable: true),
                        new OA\Property(property: 'tax_type', type: 'string', enum: ['exclusive', 'inclusive'], nullable: true),
                        new OA\Property(property: 'batch_no', type: 'string', nullable: true, description: 'Initial stock batch number'),
                        new OA\Property(property: 'qty', type: 'integer', nullable: true, description: 'Initial stock quantity'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Item created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'productName' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'barcode' => 'nullable|string|max:50',
            'sku' => 'nullable|string|max:50',
            'purchase_without_tax' => 'required|numeric|min:0',
            'purchase_with_tax' => 'required|numeric|min:0',
            'sales_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'alert_qty' => 'nullable|integer|min:0',
            'tax_type' => 'nullable|in:exclusive,inclusive',
            'product_type' => 'nullable|string',
            'dosage_form' => 'nullable|string',
            'strength' => 'nullable|string',
            'scientific_name' => 'nullable|string',
            'description' => 'nullable|string',
            'batch_no' => 'nullable|string',
            'qty' => 'nullable|integer|min:0',
        ]);

        $product = $this->productService->createProduct($validated, $request->user()->business_id);

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully.',
            'data' => $product->load(['category:id,categoryName', 'manufacturer:id,name']),
        ], 201);
    }

    /**
     * Update an existing item.
     */
    #[OA\Put(
        path: '/api/v2/items/{id}',
        summary: 'Update item',
        description: 'Update an existing product/item. Only provided fields are updated.',
        tags: ['Items V2'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'productName', type: 'string'),
                        new OA\Property(property: 'category_id', type: 'integer'),
                        new OA\Property(property: 'sales_price', type: 'number'),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Item updated'),
            new OA\Response(response: 404, description: 'Item not found'),
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $businessId = $request->user()->business_id;

        $product = Product::where('id', $id)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $validated = $request->validate([
            'productName' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'unit_id' => 'sometimes|required|exists:units,id',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'barcode' => 'nullable|string|max:50',
            'sku' => 'nullable|string|max:50',
            'purchase_without_tax' => 'sometimes|required|numeric|min:0',
            'purchase_with_tax' => 'sometimes|required|numeric|min:0',
            'sales_price' => 'sometimes|required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'alert_qty' => 'nullable|integer|min:0',
            'tax_type' => 'nullable|in:exclusive,inclusive',
            'is_active' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ]);

        $updated = $this->productService->updateProduct($product, $validated, $businessId);

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully.',
            'data' => $updated,
        ]);
    }

    /**
     * Delete (archive) an item.
     */
    #[OA\Delete(
        path: '/api/v2/items/{id}',
        summary: 'Delete item',
        description: 'Delete or archive an item. Soft-deletes if the item has transaction history.',
        tags: ['Items V2'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Item deleted'),
            new OA\Response(response: 404, description: 'Item not found'),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $businessId = $request->user()->business_id;

        $product = Product::where('id', $id)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $this->productService->deleteProduct($product);

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.',
        ]);
    }

    /**
     * Search items (optimized for POS / autocomplete).
     */
    #[OA\Get(
        path: '/api/v2/items/search',
        summary: 'Search items',
        description: 'Lightweight search endpoint optimized for POS and autocomplete. Returns minimal fields.',
        tags: ['Items V2'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string'), description: 'Search term'),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 50), description: 'Max results'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Search results'),
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;
        $term = $request->input('q', '');
        $limit = min((int) $request->input('limit', 20), 50);

        $products = $this->productService->searchProducts($term, $businessId, null, $limit);

        return response()->json([
            'success' => true,
            'data' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->productName,
                'barcode' => $p->barcode,
                'sku' => $p->sku,
                'price' => $p->sales_price,
                'purchase_price' => $p->purchase_with_tax,
                'unit' => $p->unit->unitName ?? null,
                'stock' => $p->stocks->sum('productStock'),
                'prescription_required' => $p->prescription_required,
            ]),
        ]);
    }

    /**
     * Get stock movements for an item.
     */
    #[OA\Get(
        path: '/api/v2/items/{id}/stock-movements',
        summary: 'Get stock movements',
        description: 'Returns paginated stock movements for an item with optional type and date filters.',
        tags: ['Items V2'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['in', 'out', 'adjustment']), description: 'Filter by movement type'),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Start date (YYYY-MM-DD)'),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'End date (YYYY-MM-DD)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated stock movements'),
            new OA\Response(response: 404, description: 'Item not found'),
        ]
    )]
    public function stockMovements(Request $request, int $id): JsonResponse
    {
        $businessId = $request->user()->business_id;

        // Verify product exists and belongs to user's business
        Product::where('id', $id)->where('business_id', $businessId)->firstOrFail();

        $perPage = min((int) $request->input('per_page', 20), 100);

        $query = StockMovement::where('product_id', $id)
            ->with(['user:id,name', 'stock:id,batch_no']);

        if ($request->filled('type')) {
            $query->where('movement_type', $request->input('type'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $movements = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $movements->items(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
        ]);
    }

    /**
     * Get KPIs for an item.
     */
    #[OA\Get(
        path: '/api/v2/items/{id}/kpis',
        summary: 'Get item KPIs',
        description: 'Returns key performance indicators for an item: stock levels, pricing, sales, and margin data.',
        tags: ['Items V2'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Item KPIs'),
            new OA\Response(response: 404, description: 'Item not found'),
        ]
    )]
    public function kpis(Request $request, int $id): JsonResponse
    {
        $businessId = $request->user()->business_id;

        Product::where('id', $id)->where('business_id', $businessId)->firstOrFail();

        $kpis = $this->productService->getProductKPIs($id, $businessId);

        return response()->json([
            'success' => true,
            'data' => $kpis,
        ]);
    }
}
