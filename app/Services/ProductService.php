<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\StockNotFoundException;
use App\Models\AuditLog;
use App\Models\ItemPriceHistory;
use App\Models\ItemSupplier;
use App\Models\Product;
use App\Models\Stock;
use App\Services\Stock\StockAllocationService;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductService
{
    use WithTransactionalOperations;

    public function __construct(
        private StockAllocationService $stockAllocationService
    ) {}

    /**
     * List products with advanced filtering, search, and pagination.
     */
    public function list(array $filters, int $businessId, int $perPage = 15)
    {
        $query = Product::select(
            'id', 'productName', 'productCode', 'sku', 'internal_code',
            'scientific_name', 'barcode', 'category_id', 'unit_id',
            'manufacturer_id', 'brand_id', 'purchase_without_tax', 'purchase_with_tax',
            'sales_price', 'wholesale_price', 'minimum_selling_price', 'alert_qty',
            'reorder_point', 'stock_status', 'active', 'archived', 'discontinued',
            'tax_id', 'tax_type', 'track_inventory', 'images',
            'prescription_required', 'dosage_form', 'strength', 'branch_id',
            'created_at', 'updated_at'
        )
            ->where('business_id', $businessId)
            ->with([
                'category:id,categoryName',
                'unit:id,unitName',
                'manufacturer:id,name',
            ]);

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        // Subcategory filter
        if (!empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
        }

        // Brand filter
        if (!empty($filters['brand_id'])) {
            $query->byBrand($filters['brand_id']);
        }

        // Manufacturer filter
        if (!empty($filters['manufacturer_id'])) {
            $query->byManufacturer($filters['manufacturer_id']);
        }

        // Active/Inactive filter
        if (isset($filters['active']) && $filters['active'] !== '') {
            $query->where('active', $filters['active'] === 'true' || $filters['active'] === '1');
        }

        // Stock status filter
        if (!empty($filters['stock_status'])) {
            $query->byStockStatus($filters['stock_status']);
        }

        // Prescription required filter
        if (isset($filters['prescription_required']) && $filters['prescription_required'] === 'true') {
            $query->prescriptionRequired();
        }

        // Price range
        if (!empty($filters['min_price'])) {
            $query->where('sales_price', '>=', $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $query->where('sales_price', '<=', $filters['max_price']);
        }

        // Tax filter
        if (!empty($filters['tax_id'])) {
            $query->where('tax_id', $filters['tax_id']);
        }

        // Expiration filter
        if (!empty($filters['expire_date'])) {
            $query->whereHas('stocks', function ($q) use ($filters) {
                $q->whereBetween('expire_date', [today(), $filters['expire_date']]);
            });
        }

        if (isset($filters['expired']) && $filters['expired'] == 'true') {
            $query->hasExpired();
        }

        if (isset($filters['expiring_soon']) && $filters['expiring_soon'] == 'true') {
            $days = $filters['expiring_days'] ?? 30;
            $query->expiringSoon($days);
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = $filters['direction'] ?? 'desc';
        $allowedSorts = [
            'productName', 'productCode', 'sku', 'barcode',
            'sales_price', 'purchase_with_tax', 'stock_status',
            'created_at', 'updated_at',
        ];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest();
        }

        // Add stock sum
        $query->withSum('stocks', 'productStock');

        // Branch filter
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Show a single product with all relations.
     */
    public function show(int $id, int $businessId): Product
    {
        return Product::where('business_id', $businessId)
            ->with([
                'unit:id,unitName',
                'purchaseUnit:id,unitName',
                'salesUnit:id,unitName',
                'medicine_type:id,name',
                'manufacturer:id,name',
                'box_size:id,name',
                'category:id,categoryName',
                'subcategory:id,categoryName',
                'brand:id,name',
                'tax:id,rate,name',
                'preferredSupplier:id,name,phone',
                'createdByUser:id,name',
                'updatedByUser:id,name',
                'barcodes:id,product_id,barcode_number,barcode_type,is_active',
                'itemSuppliers' => function ($q) {
                    $q->with('supplier:id,name,phone,company_name');
                },
                'allStocks' => function ($q) {
                    $q->select('id', 'product_id', 'productStock', 'batch_no', 'expire_date', 'purchase_price', 'cost_price', 'branch_id');
                },
            ])
            ->withSum('stocks', 'productStock')
            ->findOrFail($id);
    }

    /**
     * Create a product with stock and audit logging.
     */
    public function createProduct(array $data, int $businessId): Product
    {
        return $this->executeTransaction(function () use ($data, $businessId) {
            // Generate internal code if not provided
            if (empty($data['internal_code']) && empty($data['productCode'])) {
                $data['internal_code'] = Product::generateInternalCode($businessId);
            }

            // Sync SKU / productCode
            if (empty($data['productCode']) && !empty($data['sku'])) {
                $data['productCode'] = $data['sku'];
            } elseif (empty($data['sku']) && !empty($data['productCode'])) {
                $data['sku'] = $data['productCode'];
            }

            // Auto-generate barcode if type is specified
            if (empty($data['barcode']) && !empty($data['barcode_type'])) {
                $data['barcode'] = Barcode::generateBarcodeNumber($data['barcode_type']);
            }

            $product = Product::create($data + [
                'business_id' => $businessId,
                'active' => $data['active'] ?? true,
                'track_inventory' => $data['track_inventory'] ?? true,
                'track_expiration' => $data['track_expiration'] ?? false,
            ]);

            // Create initial stock
            $stockData = [
                'business_id' => $businessId,
                'product_id' => $product->id,
                'productStock' => 0,
                'batch_no' => $data['batch_no'] ?? null,
                'expire_date' => $data['expire_date'] ?? null,
                'purchase_price' => $data['purchase_with_tax'] ?? $data['purchase_without_tax'] ?? 0,
                'barcode' => $data['barcode'] ?? null,
            ];

            if (isset($data['branch_id'])) {
                $stockData['branch_id'] = $data['branch_id'];
            }

            $stock = Stock::create($stockData);

            // Add initial stock quantity
            $quantity = $data['qty'] ?? 0;
            if ($quantity > 0) {
                $this->stockAllocationService->addStock(
                    $stock,
                    $quantity,
                    Product::class,
                    $product->id,
                    auth()->id() ?? 0,
                    'Initial stock creation'
                );
            }

            // Save supplier relationships
            if (!empty($data['supplier_ids']) && is_array($data['supplier_ids'])) {
                $this->syncSuppliers($product, $data['supplier_ids'], $businessId, $data);
            }

            // Audit log
            $this->logAudit('created', $product, null, $product->toArray());

            return $product->fresh();
        });
    }

    /**
     * Update a product with audit logging.
     */
    public function updateProduct(Product $product, array $data, int $businessId): Product
    {
        return $this->executeTransaction(function () use ($product, $data, $businessId) {
            $oldValues = $product->toArray();

            // Handle images
            if (isset($data['removed_images'])) {
                $prevImages = array_diff($product->images ?? [], $data['removed_images']);
                foreach ($data['removed_images'] as $image) {
                    if (Storage::exists($image)) {
                        Storage::delete($image);
                    }
                }
                $prevImages = array_values($prevImages);
            } else {
                $prevImages = $product->images ?? [];
            }
            $newImages = $data['images'] ?? [];
            $mergedImages = array_merge($prevImages, $newImages);

            // Sync SKU / productCode
            if (isset($data['sku']) && empty($data['productCode'])) {
                $data['productCode'] = $data['sku'];
            } elseif (isset($data['productCode']) && empty($data['sku'])) {
                $data['sku'] = $data['productCode'];
            }

            // Update stock info
            $stock = Stock::where('product_id', $product->id)->first();
            $qtyToAdd = $data['qty'] ?? 0;

            if ($stock) {
                $stock->update([
                    'batch_no' => $data['batch_no'] ?? $stock->batch_no,
                    'expire_date' => $data['expire_date'] ?? $stock->expire_date,
                ]);

                if ($qtyToAdd > 0) {
                    $this->stockAllocationService->addStock(
                        $stock,
                        $qtyToAdd,
                        Product::class,
                        $product->id,
                        auth()->id() ?? 0,
                        'Added stock during product update'
                    );
                }
            } else {
                $stockData = [
                    'product_id' => $product->id,
                    'business_id' => $businessId,
                    'productStock' => 0,
                    'batch_no' => $data['batch_no'] ?? null,
                    'expire_date' => $data['expire_date'] ?? null,
                ];
                if (isset($data['branch_id'])) {
                    $stockData['branch_id'] = $data['branch_id'];
                }
                $stock = Stock::create($stockData);

                if ($qtyToAdd > 0) {
                    $this->stockAllocationService->addStock(
                        $stock,
                        $qtyToAdd,
                        Product::class,
                        $product->id,
                        auth()->id() ?? 0,
                        'Initial stock added during product update'
                    );
                }
            }

            // Update product
            $updateData = array_merge($data, ['images' => $mergedImages]);
            // Remove fields that shouldn't be updated directly
            unset($updateData['qty'], $updateData['batch_no'], $updateData['expire_date']);
            $product->update($updateData);

            // Update stock status
            $product->updateStockStatus();

            // Update supplier relationships
            if (isset($data['supplier_ids']) && is_array($data['supplier_ids'])) {
                $this->syncSuppliers($product, $data['supplier_ids'], $businessId, $data);
            }

            // Record price history if prices changed
            $priceFields = ['purchase_without_tax', 'purchase_with_tax', 'sales_price', 'wholesale_price', 'minimum_selling_price'];
            $pricesChanged = false;
            foreach ($priceFields as $field) {
                if (isset($oldValues[$field]) && isset($data[$field]) && $oldValues[$field] != $data[$field]) {
                    $pricesChanged = true;
                    break;
                }
            }
            if ($pricesChanged) {
                ItemPriceHistory::create([
                    'product_id' => $product->id,
                    'business_id' => $businessId,
                    'user_id' => auth()->id(),
                    'purchase_without_tax' => $product->purchase_without_tax,
                    'purchase_with_tax' => $product->purchase_with_tax,
                    'sales_price' => $product->sales_price,
                    'wholesale_price' => $product->wholesale_price,
                    'minimum_selling_price' => $product->minimum_selling_price,
                    'change_reason' => 'manual',
                ]);
            }

            // Audit log
            $newValues = $product->fresh()->toArray();
            $this->logAudit('updated', $product, $oldValues, $newValues);

            return $product->fresh();
        });
    }

    /**
     * Sync items from a purchase invoice. Creates new items or updates stock for existing ones.
     */
    public function syncPurchaseItems(array $items, int $businessId, int $userId, ?string $referenceType = null, ?int $referenceId = null): array
    {
        $created = [];
        $updated = [];

        foreach ($items as $item) {
            $barcode = $item['barcode'] ?? null;
            $productName = $item['product_name'] ?? null;
            $productId = $item['product_id'] ?? null;

            $product = null;

            // Try to find existing product
            if ($productId) {
                $product = Product::where('id', $productId)
                    ->where('business_id', $businessId)
                    ->first();
            }
            if (!$product && $barcode) {
                $product = $this->searchByBarcode($barcode, $businessId);
            }

            if ($product) {
                // Update stock — add purchased quantity
                $stock = Stock::where('product_id', $product->id)
                    ->where('business_id', $businessId)
                    ->first();

                if (!$stock) {
                    $stock = Stock::create([
                        'product_id' => $product->id,
                        'business_id' => $businessId,
                        'productStock' => 0,
                        'batch_no' => $item['batch_no'] ?? null,
                        'expire_date' => $item['expire_date'] ?? null,
                        'purchase_price' => $item['purchase_with_tax'] ?? $item['purchase_price'] ?? null,
                        'barcode' => $barcode,
                    ]);
                }

                $qty = $item['quantities'] ?? $item['qty'] ?? 0;
                if ($qty > 0) {
                    $this->stockAllocationService->addStock(
                        $stock, $qty, $referenceType ?? Product::class,
                        $referenceId ?? $product->id, $userId,
                        'Added via purchase invoice'
                    );
                }

                // Update purchase price on the product if provided
                $purchasePrice = $item['purchase_with_tax'] ?? $item['purchase_price'] ?? null;
                if ($purchasePrice !== null) {
                    $product->update([
                        'purchase_with_tax' => $purchasePrice,
                        'purchase_without_tax' => $item['purchase_without_tax'] ?? $purchasePrice,
                    ]);
                }

                $product->updateStockStatus();
                $updated[] = $product;
            } elseif ($productName) {
                // Create new product from purchase data
                $newProductData = [
                    'productName' => $productName,
                    'barcode' => $barcode,
                    'sku' => $item['sku'] ?? null,
                    'purchase_with_tax' => $item['purchase_with_tax'] ?? $item['purchase_price'] ?? 0,
                    'purchase_without_tax' => $item['purchase_without_tax'] ?? $item['purchase_with_tax'] ?? 0,
                    'sales_price' => $item['sales_price'] ?? 0,
                    'wholesale_price' => $item['wholesale_price'] ?? 0,
                    'category_id' => $item['category_id'] ?? null,
                    'unit_id' => $item['unit_id'] ?? null,
                    'manufacturer_id' => $item['manufacturer_id'] ?? null,
                    'qty' => $item['quantities'] ?? $item['qty'] ?? 0,
                    'batch_no' => $item['batch_no'] ?? null,
                    'expire_date' => $item['expire_date'] ?? null,
                ];

                try {
                    $product = $this->createProduct($newProductData, $businessId);
                    $created[] = $product;
                } catch (\Exception $e) {
                    \Log::warning('Failed to auto-create product from purchase', [
                        'name' => $productName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Update stock for a product.
     */
    public function updateStock(int $productId, array $data, int $businessId): Product
    {
        return $this->executeTransaction(function () use ($productId, $data, $businessId) {
            $product = Product::where('id', $productId)
                ->where('business_id', $businessId)
                ->firstOrFail();

            $oldValues = $product->toArray();
            $product->update($data);

            $stock = Stock::where('product_id', $product->id)
                ->where('batch_no', $data['batch_no'] ?? null)
                ->first();

            $qtyAdjustment = $data['qty'] ?? 0;

            if ($stock) {
                $stock->update([
                    'batch_no' => $data['batch_no'] ?? $stock->batch_no,
                    'expire_date' => $data['expire_date'] ?? $stock->expire_date,
                ]);

                if ($qtyAdjustment > 0) {
                    $this->stockAllocationService->addStock(
                        $stock,
                        $qtyAdjustment,
                        Product::class,
                        $product->id,
                        auth()->id() ?? 0,
                        'Stock updated manually (addition)'
                    );
                } elseif ($qtyAdjustment < 0) {
                    $qtyToDeduct = abs($qtyAdjustment);
                    if ($stock->productStock < $qtyToDeduct) {
                        throw new InsufficientStockException(
                            message: "Insufficient stock for {$product->productName}. Available: {$stock->productStock}, Requested: {$qtyToDeduct}",
                            errors: [
                                'product_id' => $product->id,
                                'available' => $stock->productStock,
                                'requested' => $qtyToDeduct,
                            ]
                        );
                    }
                    $this->stockAllocationService->allocate(
                        $stock,
                        $qtyToDeduct,
                        Product::class,
                        $product->id,
                        auth()->id() ?? 0,
                        'Stock updated manually (deduction)'
                    );
                }
            } else {
                $stockData = [
                    'product_id' => $product->id,
                    'productStock' => 0,
                    'expire_date' => $data['expire_date'] ?? null,
                    'business_id' => $businessId,
                ];
                if (isset($data['branch_id'])) {
                    $stockData['branch_id'] = $data['branch_id'];
                }
                $stock = Stock::create($stockData);

                if ($qtyAdjustment > 0) {
                    $this->stockAllocationService->addStock(
                        $stock,
                        $qtyAdjustment,
                        Product::class,
                        $product->id,
                        auth()->id() ?? 0,
                        'Stock initialized manually'
                    );
                }
            }

            // Update stock status
            $product->updateStockStatus();

            $newValues = $product->fresh()->toArray();
            $this->logAudit('stock_updated', $product, $oldValues, $newValues);

            return $product->fresh();
        });
    }

    /**
     * Soft delete or archive a product depending on transactions.
     */
    public function deleteProduct(Product $product): bool
    {
        if ($product->hasTransactions()) {
            // Archive instead of delete
            $product->update(['archived' => true, 'active' => false]);
            $this->logAudit('archived', $product, ['archived' => false], ['archived' => true]);

            return true;
        }

        // Safe to soft-delete
        foreach ($product->images ?? [] as $image) {
            if (Storage::exists($image)) {
                Storage::delete($image);
            }
        }

        $this->logAudit('deleted', $product, $product->toArray(), null);

        return $product->delete();
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restoreProduct(int $productId, int $businessId): Product
    {
        $product = Product::withTrashed()
            ->where('business_id', $businessId)
            ->findOrFail($productId);

        $product->restore();
        $this->logAudit('restored', $product, null, ['restored_at' => now()]);

        return $product;
    }

    /**
     * Bulk update products.
     */
    public function bulkUpdate(array $productIds, array $data, int $businessId): array
    {
        return $this->executeTransaction(function () use ($productIds, $data, $businessId) {
            $products = Product::whereIn('id', $productIds)
                ->where('business_id', $businessId)
                ->get();

            $success = 0;
            $failed = 0;
            $errors = [];

            foreach ($products as $product) {
                try {
                    $oldValues = $product->toArray();
                    $product->update($data);
                    $this->logAudit('bulk_updated', $product, $oldValues, $data);
                    $success++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'success_count' => $success,
                'failed_count' => $failed,
                'errors' => $errors,
            ];
        });
    }

    /**
     * Check for duplicate items.
     */
    public function checkDuplicates(array $data, ?int $excludeId = null): array
    {
        return Product::findDuplicates($data, $excludeId);
    }

    /**
     * Search products for POS / Purchase / Sales integration.
     */
    public function searchProducts(string $term, int $businessId, ?int $branchId = null, int $limit = 20)
    {
        $query = Product::select(
            'id', 'productName', 'productCode', 'sku', 'barcode',
            'sales_price', 'purchase_without_tax', 'purchase_with_tax',
            'wholesale_price', 'minimum_selling_price', 'tax_id', 'tax_type',
            'track_inventory', 'prescription_required', 'branch_id',
            'dosage_form', 'strength'
        )
            ->where('business_id', $businessId)
            ->where('active', true)
            ->where('archived', false)
            ->search($term)
            ->withSum('stocks', 'productStock')
            ->limit($limit);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        return $query->get();
    }

    /**
     * Search product by barcode.
     */
    public function searchByBarcode(string $barcode, int $businessId): ?Product
    {
        // First check product.barcode
        $product = Product::where('business_id', $businessId)
            ->where('barcode', $barcode)
            ->where('active', true)
            ->first();

        if ($product) {
            return $product;
        }

        // Then check the barcodes table
        $barcodeRecord = \App\Models\Barcode::where('barcode_number', $barcode)
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if ($barcodeRecord) {
            return Product::find($barcodeRecord->product_id);
        }

        // Check stock barcode
        $stock = Stock::where('barcode', $barcode)
            ->where('business_id', $businessId)
            ->first();

        if ($stock) {
            return Product::find($stock->product_id);
        }

        return null;
    }

    /**
     * Get products with stock for purchase/sales forms.
     */
    public function getProductsWithStock(array $filters, int $businessId, int $perPage = 10)
    {
        $query = Stock::select('id', 'expire_date', 'product_id', 'batch_no', 'productStock')
            ->with([
                'product.tax:id,rate,name',
                'product:id,productName,purchase_without_tax,purchase_with_tax,profit_percent,sales_price,wholesale_price,tax_id,tax_type,productCode,barcode',
            ])
            ->where('business_id', $businessId)
            ->latest();

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($subQuery) use ($term) {
                $subQuery->where('batch_no', 'like', $term)
                    ->orWhereHas('product', function ($query) use ($term) {
                        $query->where('productName', 'like', $term)
                            ->orWhere('productCode', 'like', $term)
                            ->orWhere('barcode', 'like', $term);
                    });
            });
        }

        if (isset($filters['check_stock']) && $filters['check_stock'] == 'true') {
            $query->where('productStock', '>', 0);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get KPIs for the product details page.
     */
    public function getProductKPIs(int $productId, int $businessId): array
    {
        $product = Product::where('id', $productId)
            ->where('business_id', $businessId)
            ->withSum('stocks', 'productStock')
            ->firstOrFail();

        $totalStock = (int) $product->stocks_sum_productstock;

        return [
            'current_stock' => $totalStock,
            'available_stock' => $totalStock,
            'reserved_stock' => 0,
            'average_cost' => $product->purchase_with_tax,
            'last_purchase_price' => $product->purchase_with_tax,
            'current_selling_price' => $product->getCurrentSellingPriceAttribute(),
            'total_sold' => $product->getTotalSoldAttribute(),
            'total_purchased' => $product->getTotalPurchasedAttribute(),
            'reorder_status' => $product->getStockStatusDisplayAttribute(),
            'profit_margin' => $product->getProfitMarginAttribute(),
        ];
    }

    /**
     * Export products to array for CSV/Excel.
     */
    public function exportProducts(int $businessId, array $filters = []): array
    {
        $query = Product::where('business_id', $businessId)
            ->with([
                'category:id,categoryName',
                'unit:id,unitName',
                'manufacturer:id,name',
                'brand:id,name',
            ])
            ->withSum('stocks', 'productStock');

        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }
        if (!empty($filters['brand_id'])) {
            $query->byBrand($filters['brand_id']);
        }
        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        $products = $query->get();

        return $products->map(function ($product) {
            return [
                'Product Name' => $product->productName,
                'SKU' => $product->sku ?? $product->productCode,
                'Internal Code' => $product->internal_code,
                'Barcode' => $product->barcode,
                'Scientific Name' => $product->scientific_name,
                'Category' => $product->category->categoryName ?? '',
                'Manufacturer' => $product->manufacturer->name ?? '',
                'Brand' => $product->brand->name ?? '',
                'Unit' => $product->unit->unitName ?? '',
                'Purchase Price' => $product->purchase_with_tax ?? $product->purchase_without_tax,
                'Selling Price' => $product->sales_price,
                'Wholesale Price' => $product->wholesale_price,
                'Minimum Selling Price' => $product->minimum_selling_price,
                'Current Stock' => $product->stocks_sum_productstock ?? 0,
                'Reorder Point' => $product->reorder_point,
                'Alert Qty' => $product->alert_qty,
                'Stock Status' => $product->stock_status,
                'Active' => $product->active ? 'Yes' : 'No',
                'Prescription Required' => $product->prescription_required ? 'Yes' : 'No',
                'Track Inventory' => $product->track_inventory ? 'Yes' : 'No',
                'Description' => $product->description,
            ];
        })->toArray();
    }

    /**
     * Import products from array data.
     */
    public function importProducts(array $rows, int $businessId): array
    {
        $success = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                try {
                    $data = $this->normalizeImportRow($row, $businessId);

                    // Check duplicates
                    $duplicates = Product::findDuplicates($data);
                    if (!empty($duplicates['barcode']) || !empty($duplicates['sku'])) {
                        $failed++;
                        $errors[] = [
                            'row' => $index + 1,
                            'error' => 'Duplicate found: ' . ($duplicates['barcode'] ? 'barcode' : 'SKU'),
                        ];
                        continue;
                    }

                    $product = $this->createProduct($data, $businessId);
                    $success++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            return [
                'success_count' => $success,
                'failed_count' => $failed,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Normalize an import row to product data.
     */
    private function normalizeImportRow(array $row, int $businessId): array
    {
        $data = [
            'productName' => $row['Product Name'] ?? $row['productName'] ?? $row['name'] ?? null,
            'sku' => $row['SKU'] ?? $row['sku'] ?? null,
            'productCode' => $row['SKU'] ?? $row['productCode'] ?? null,
            'barcode' => $row['Barcode'] ?? $row['barcode'] ?? null,
            'scientific_name' => $row['Scientific Name'] ?? $row['scientific_name'] ?? null,
            'description' => $row['Description'] ?? $row['description'] ?? null,
            'purchase_without_tax' => $row['Purchase Price'] ?? $row['purchase_without_tax'] ?? 0,
            'purchase_with_tax' => $row['Purchase Price'] ?? $row['purchase_with_tax'] ?? 0,
            'sales_price' => $row['Selling Price'] ?? $row['sales_price'] ?? 0,
            'wholesale_price' => $row['Wholesale Price'] ?? $row['wholesale_price'] ?? 0,
            'minimum_selling_price' => $row['Minimum Selling Price'] ?? $row['minimum_selling_price'] ?? null,
            'alert_qty' => $row['Alert Qty'] ?? $row['alert_qty'] ?? 0,
            'reorder_point' => $row['Reorder Point'] ?? $row['reorder_point'] ?? 0,
            'track_inventory' => in_array(strtolower($row['Track Inventory'] ?? $row['track_inventory'] ?? 'yes'), ['yes', 'true', '1']),
            'prescription_required' => in_array(strtolower($row['Prescription Required'] ?? $row['prescription_required'] ?? 'no'), ['yes', 'true', '1']),
            'active' => strtolower($row['Active'] ?? $row['active'] ?? 'yes') !== 'no',
        ];

        // Resolve category by name
        if (!empty($row['Category'] ?? $row['category'] ?? null)) {
            $catName = $row['Category'] ?? $row['category'];
            $category = \App\Models\Category::where('business_id', $businessId)
                ->whereRaw('LOWER(categoryName) = ?', [strtolower($catName)])
                ->first();
            if ($category) {
                $data['category_id'] = $category->id;
            }
        }

        // Resolve manufacturer by name
        if (!empty($row['Manufacturer'] ?? $row['manufacturer'] ?? null)) {
            $mfgName = $row['Manufacturer'] ?? $row['manufacturer'];
            $mfg = \App\Models\Manufacturer::where('business_id', $businessId)
                ->whereRaw('LOWER(name) = ?', [strtolower($mfgName)])
                ->first();
            if ($mfg) {
                $data['manufacturer_id'] = $mfg->id;
            }
        }

        // Resolve unit by name
        if (!empty($row['Unit'] ?? $row['unit'] ?? null)) {
            $unitName = $row['Unit'] ?? $row['unit'];
            $unit = \App\Models\Unit::where('business_id', $businessId)
                ->whereRaw('LOWER(unitName) = ?', [strtolower($unitName)])
                ->first();
            if ($unit) {
                $data['unit_id'] = $unit->id;
            }
        }

        return array_filter($data, fn($v) => $v !== null);
    }

    /**
     * Sync supplier relationships.
     */
    private function syncSuppliers(Product $product, array $supplierIds, int $businessId, array $data): void
    {
        // Remove existing
        ItemSupplier::where('product_id', $product->id)->delete();

        foreach ($supplierIds as $supplierId) {
            ItemSupplier::create([
                'product_id' => $product->id,
                'supplier_id' => $supplierId,
                'business_id' => $businessId,
                'supplier_item_code' => $data['supplier_item_code'] ?? null,
                'supplier_purchase_price' => $data['supplier_purchase_price'] ?? null,
                'is_preferred' => ($supplierId == ($data['preferred_supplier_id'] ?? null)),
            ]);
        }
    }

    /**
     * Log an audit entry for product changes.
     */
    private function logAudit(string $action, Product $product, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => "Product {$action}: {$product->productName} (ID: {$product->id})",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta' => [
                'model_type' => Product::class,
                'model_id' => $product->id,
                'business_id' => $product->business_id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ],
        ]);
    }
}
