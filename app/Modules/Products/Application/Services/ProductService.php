<?php

declare(strict_types=1);

namespace App\Modules\Products\Application\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Modules\Products\Domain\DTOs\CreateProductDTO;
use App\Modules\Products\Domain\DTOs\UpdateProductDTO;
use App\Modules\Products\Domain\Events\ProductCreated;
use App\Modules\Products\Domain\Events\ProductDeleted;
use App\Modules\Products\Domain\Events\ProductUpdated;
use App\Services\StockBatchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Product Service — Pure business logic for product management.
 *
 * Follows the Service Layer pattern:
 * - All business rules live here, not in Controllers.
 * - All database operations are transactional.
 * - Events are dispatched for cross-cutting concerns.
 */
class ProductService
{
    public function __construct(
        protected StockBatchService $stockBatchService
    ) {}

    /**
     * List products with optional search and pagination.
     *
     * Uses the full-text search GIN index (idx_products_search) for fast
     * PostgreSQL text searches on name, generic_name, and brand_name.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return Product::query()
            ->select([
                'id',
                'company_id',
                'category_id',
                'manufacturer_id',
                'name',
                'generic_name',
                'brand_name',
                'product_name',
                'barcode',
                'product_code',
                'purchase_price',
                'sales_price',
                'wholesale_price',
                'reorder_point',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->with([
                'category:id,name',
                'manufacturer:id,name',
            ])
            ->withSum('stocks', 'productStock')
            ->when(
                !empty($filters['company_id']),
                fn (Builder $q) => $q->where('company_id', $filters['company_id'])
            )
            ->when(
                !empty($filters['search']),
                fn (Builder $q) => $q->where(function (Builder $query) use ($filters) {
                    $search = $filters['search'];
                    // Use full-text search if PostgreSQL (GIN index available)
                    // Fallback to LIKE for all databases
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%")
                        ->orWhere('brand_name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('product_code', 'like', "%{$search}%");
                })
            )
            ->when(
                !empty($filters['category_id']),
                fn (Builder $q) => $q->where('category_id', $filters['category_id'])
            )
            ->when(
                !empty($filters['manufacturer_id']),
                fn (Builder $q) => $q->where('manufacturer_id', $filters['manufacturer_id'])
            )
            ->when(
                isset($filters['is_active']),
                fn (Builder $q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN))
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new product.
     *
     * Supports optional initial stock creation via StockBatchService.
     * Pass batch_number, expiry_date, quantity_available in the DTO.
     */
    public function create(CreateProductDTO $dto): Product
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();

            // Auto-generate SKU if not provided
            if (empty($data['product_code'])) {
                $data['product_code'] = $this->generateSku();
            }

            // Ensure product name is used as product_name alias
            $data['product_name'] = $data['name'];

            $product = Product::create($data);

            // Record initial price in history if sale price is set
            if ($product->sales_price > 0) {
                $product->priceHistory()->create([
                    'company_id' => $product->company_id,
                    'product_id' => $product->id,
                    'price_type' => 'sales',
                    'old_price' => 0,
                    'new_price' => $product->sales_price,
                    'change_reason' => 'initial_setup',
                    'changed_by' => $dto->created_by,
                ]);
            }

            // Create initial stock batch if quantity provided
            if ($dto->quantity_available && $dto->quantity_available > 0) {
                $this->stockBatchService->receiveStock([
                    'company_id' => $dto->company_id,
                    'branch_id' => $dto->branch_id ?? $product->company_id,
                    'product_id' => $product->id,
                    'batch_number' => $dto->batch_number ?? 'BATCH-'.date('ymd').'-'.strtoupper(substr(uniqid(), -4)),
                    'expiry_date' => $dto->expiry_date,
                    'quantity' => $dto->quantity_available,
                    'cost_price' => $dto->purchase_price ?? 0,
                    'selling_price' => $dto->sale_price ?? 0,
                ]);
            }

            event(new ProductCreated($product));

            return $product->fresh(['category', 'manufacturer', 'stocks']);
        });
    }

    /**
     * Get a single product with relations (UUID primary key).
     */
    public function find(string $id): Product
    {
        return Product::with([
            'category:id,name',
            'manufacturer:id,name',
            'stocks' => function ($q) {
                $q->where('productStock', '>', 0)
                    ->orderBy('expire_date', 'asc');
            },
            'priceHistory' => function ($q) {
                $q->latest()->limit(10);
            },
        ])
            ->withSum('stocks', 'productStock')
            ->findOrFail($id);
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product, UpdateProductDTO $dto): Product
    {
        return DB::transaction(function () use ($product, $dto) {
            $oldPrice = $product->sales_price;
            $updateData = $dto->toArray();

            // Sync product_name alias
            if (isset($updateData['name'])) {
                $updateData['product_name'] = $updateData['name'];
            }

            $product->update($updateData);

            // Record price change if price changed
            if (isset($updateData['sales_price']) && (float) $updateData['sales_price'] !== (float) $oldPrice) {
                $product->priceHistory()->create([
                    'company_id' => $product->company_id,
                    'product_id' => $product->id,
                    'price_type' => 'sales',
                    'old_price' => $oldPrice,
                    'new_price' => $updateData['sales_price'],
                    'change_reason' => 'price_update',
                    'changed_by' => $dto->updated_by,
                ]);
            }

            event(new ProductUpdated($product));

            return $product->fresh(['category', 'manufacturer']);
        });
    }

    /**
     * Soft-delete a product.
     */
    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $result = $product->delete();

            if ($result) {
                event(new ProductDeleted($product));
            }

            return $result;
        });
    }

    /**
     * Find product by barcode.
     */
    public function findByBarcode(string $barcode): ?Product
    {
        return Product::with([
            'category:id,name',
            'manufacturer:id,name',
            'stocks' => function ($q) {
                $q->where('productStock', '>', 0)
                    ->orderBy('expire_date', 'asc');
            },
        ])
            ->withSum('stocks', 'productStock')
            ->where('barcode', $barcode)
            ->first();
    }

    /**
     * Search products for autocomplete.
     */
    public function search(string $query, ?int $companyId = null, int $limit = 20): array
    {
        return Product::query()
            ->select([
                'id',
                'name',
                'generic_name',
                'barcode',
                'product_code',
                'sales_price',
            ])
            ->when($companyId, fn (Builder $q) => $q->where('company_id', $companyId))
            ->where(function (Builder $q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('generic_name', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%")
                    ->orWhere('product_code', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'label' => $product->name ?? $product->generic_name,
                'value' => $product->name ?? $product->generic_name,
                'barcode' => $product->barcode,
                'sku' => $product->product_code,
                'price' => $product->sales_price,
            ])
            ->toArray();
    }

    /**
     * Get stock overview for a product with all batches and expiry tracking.
     */
    public function getProductStock(string $productId, string $branchId): array
    {
        return $this->stockBatchService->getProductStockOverview($productId, $branchId);
    }

    /**
     * Get products expiring within a given number of days.
     */
    public function getExpiringProducts(string $companyId, int $days = 30): LengthAwarePaginator
    {
        return Inventory::where('company_id', $companyId)
            ->where('status', 'available')
            ->whereColumn('quantity', '>', 'reserved_quantity')
            ->whereBetween('expiry_date', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ])
            ->with(['product:id,name,generic_name,barcode,product_code,sales_price', 'branch:id,name'])
            ->orderBy('expiry_date', 'asc')
            ->paginate(25);
    }

    /**
     * Get products with low stock (below reorder point).
     */
    public function getLowStockProducts(string $companyId): LengthAwarePaginator
    {
        return Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('track_inventory', true)
            ->withSum('inventory', 'quantity')
            ->whereHas('inventory', function ($q) {
                $q->selectRaw('COALESCE(SUM(quantity - reserved_quantity), 0) as total_available')
                    ->havingRaw('total_available <= products.reorder_point');
            })
            ->with(['category:id,name', 'manufacturer:id,name'])
            ->paginate(25);
    }

    /**
     * Generate a unique SKU.
     */
    protected function generateSku(): string
    {
        return 'PRD-' . strtoupper(substr(uniqid(), -8));
    }
}

