<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DrugInteraction;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Services\Stock\StockAllocationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Product Service - Professional Unified Pharmacy Product Management
 *
 * This is the SINGLE source of truth for all product/medicine operations.
 * Handles Product, Medicine, and Drug types in a unified way.
 *
 * Features:
 * - Full CRUD with all pharmacy-specific fields
 * - Advanced search (name, barcode, generic, ATC code)
 * - Stock batch management with FEFO (First Expiry First Out)
 * - Price history tracking with audit trail
 * - Drug interaction checking (severe & contraindicated)
 * - Bulk import/export with validation
 * - Barcode scanning support for POS
 * - Controlled substances tracking & logging
 * - Auto SKU/code generation
 * - Variants management
 */
class ProductService
{
    /**
     * Search products with advanced pharmacy-specific filters.
     */
    public function search(array $filters = []): LengthAwarePaginator
    {
        $query = Product::query()
            ->withCommon()
            ->with([
                'tax:id,name,rate',
                'variants:id,product_id,variant_name,barcode,sales_price,is_default',
            ]);

        // نص البحث (بحث في أسماء المنتجات والباركود والكود)
        if (!empty($filters['search'])) {
            $query->search(trim($filters['search']));
        }

        // البحث بالباركود (تطابق تام - أولوية عالية)
        if (!empty($filters['barcode'])) {
            $query->byBarcode($filters['barcode']);
        }

        // الفلترة حسب الفئة
        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        // الفلترة حسب المصنع/الموزع
        if (!empty($filters['manufacturer_id'])) {
            $query->byManufacturer($filters['manufacturer_id']);
        }

        // الفلترة حسب الوحدة
        if (!empty($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }

        // الفلترة حسب شكل الجرعة
        if (!empty($filters['dosage_form'])) {
            $query->byDosageForm($filters['dosage_form']);
        }

        // المواد الخاضعة للرقابة
        if (!empty($filters['is_controlled'])) {
            $query->controlled();
        }

        // الأدوية التي تحتاج وصفة طبية
        if (isset($filters['prescription_required'])) {
            $query->prescriptionRequired(filter_var($filters['prescription_required'], FILTER_VALIDATE_BOOLEAN));
        }

        // حالة التفعيل
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        // مخزون منخفض
        if (!empty($filters['low_stock'])) {
            $query->lowStock();
        }

        // نفذ من المخزون
        if (!empty($filters['out_of_stock'])) {
            $query->outOfStock();
        }

        // منتهي الصلاحية
        if (!empty($filters['expired'])) {
            $query->expired();
        }

        // قريب الانتهاء
        if (!empty($filters['expiring_soon'])) {
            $days = (int) ($filters['expiring_soon_days'] ?? 90);
            $query->expiringSoon($days);
        }

        // نطاق السعر
        if (!empty($filters['price_min']) && !empty($filters['price_max'])) {
            $query->priceBetween((float) $filters['price_min'], (float) $filters['price_max']);
        }

        // الترتيب
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['name', 'generic_name', 'brand_name', 'sales_price', 'created_at', 'updated_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min(max($perPage, 5), 100);

        return $query->paginate($perPage);
    }

    /**
     * Get product by barcode (fast lookup for POS/barcode scanners).
     */
    public function getByBarcode(string $barcode): ?Product
    {
        return Product::with([
                'category:id,name,slug',
                'manufacturer:id,name',
                'unit:id,unitName,short_code',
                'variants' => fn($q) => $q->where('is_active', true),
                'fefoBatches',
            ])
            ->byBarcode($barcode)
            ->first();
    }

    /**
     * Get product by SKU/product code.
     */
    public function getBySku(string $sku): ?Product
    {
        return Product::withCommon()
            ->where('product_code', $sku)
            ->orWhere('sku', $sku)
            ->first();
    }

    /**
     * Get product by ID with all relationships.
     */
    public function getById(string $id): Product
    {
        return Product::withCommon()
            ->withStock()
            ->with([
                'variants',
                'priceHistory' => fn($q) => $q->latest()->limit(10),
                'drugInteractions' => fn($q) => $q->wherePivotIn('interaction_level', ['severe', 'contraindicated']),
            ])
            ->findOrFail($id);
    }

    /**
     * Get autocomplete suggestions.
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        return Product::select([
                'id', 'name', 'generic_name', 'brand_name',
                'barcode', 'product_code', 'sales_price', 'strength',
            ])
            ->where(fn($q) => $q->search($query))
            ->active()
            ->limit($limit)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'label' => $p->name ?? $p->generic_name ?? $p->brand_name,
                'sublabel' => $p->brand_name ? "{$p->brand_name}" : null,
                'value' => $p->name ?? $p->generic_name,
                'price' => (float) $p->sales_price,
                'barcode' => $p->barcode,
                'strength' => $p->strength,
            ])
            ->toArray();
    }

    // ────────────────────────────── CRUD ──────────────────────────────

    /**
     * Create a new product with complete pharmacy data.
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $companyId = $data['company_id'] ?? app('tenant.company_id');
            $userId = Auth::id();

            // تحضير بيانات المنتج
            $productData = $this->prepareProductData($data, $companyId, $userId);
            $product = Product::create($productData);

            // إنشاء دفعة مخزون أولية إذا وجدت
            $this->handleInitialStock($product, $data, $companyId);

            // إنشاء المتغيرات (الأحجام المختلفة)
            $this->createVariants($product, $data['variants'] ?? [], $companyId);

            // تسجيل بداية سعر البيع في سجل الأسعار
            $this->recordPriceChange($product, 'sales', 0, (float) $product->sales_price, 'initial_setup');

            return $product->fresh()->loadCommon();
        });
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $userId = Auth::id();
            $oldSalesPrice = (float) $product->sales_price;
            $oldPurchasePrice = (float) $product->purchase_price;

            // معالجة الصور
            $this->handleImageRemovals($product, $data);
            $mergedImages = $this->mergeImages($product, $data);

            // تحضير بيانات التحديث
            $updateData = array_merge(
                $this->filterUpdatableFields($data),
                ['images' => $mergedImages, 'updated_by' => $userId]
            );

            $product->update($updateData);

            // تسجيل تغيير السعر إذا حدث
            $newSalesPrice = (float) ($data['sales_price'] ?? $oldSalesPrice);
            if ($newSalesPrice !== $oldSalesPrice) {
                $this->recordPriceChange($product, 'sales', $oldSalesPrice, $newSalesPrice, 'price_update');
            }

            $newPurchasePrice = (float) ($data['purchase_price'] ?? $oldPurchasePrice);
            if ($newPurchasePrice !== $oldPurchasePrice) {
                $this->recordPriceChange($product, 'purchase', $oldPurchasePrice, $newPurchasePrice, 'price_update');
            }

            // مزامنة المتغيرات
            if (!empty($data['variants'])) {
                $this->syncVariants($product, $data['variants']);
            }

            return $product->fresh()->loadCommon();
        });
    }

    /**
     * Delete (soft-delete) a product.
     */
    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // حذف الصور المرتبطة
            foreach ((array) ($product->images ?? []) as $image) {
                if (is_string($image) && Storage::exists($image)) {
                    Storage::delete($image);
                }
            }

            return $product->delete();
        });
    }

    /**
     * Toggle product active status.
     */
    public function toggleActive(Product $product): bool
    {
        $product->update([
            'is_active' => !$product->is_active,
            'updated_by' => Auth::id(),
        ]);
        return $product->is_active;
    }

    /**
     * Duplicate a product (for quick entry of similar products).
     */
    public function duplicate(Product $product): Product
    {
        $data = $product->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
        $data['name'] = ($product->name ?? 'منتج') . ' (نسخة)';
        $data['product_code'] = null;
        $data['barcode'] = null;
        $data['is_active'] = true;
        return $this->create($data);
    }

    // ────────────────────────────── Stock & Batch ──────────────────────────────

    /**
     * Handle initial stock creation for a product.
     */
    protected function handleInitialStock(Product $product, array $data, string $companyId): void
    {
        $quantity = $data['qty'] ?? $data['quantity'] ?? 0;
        if ((float) $quantity <= 0) {
            return;
        }

        $batchNo = $data['batch_no'] ?? $data['batch_number'] ?? $this->generateBatchNumber();
        $expiryDate = $data['expire_date'] ?? $data['expiry_date'] ?? null;

        Stock::create([
            'company_id' => $companyId,
            'product_id' => $product->id,
            'batch_no' => $batchNo,
            'expire_date' => $expiryDate,
            'productStock' => (float) $quantity,
            'purchase_price' => (float) ($data['purchase_price'] ?? 0),
        ]);
    }

    /**
     * Add a stock batch to existing product.
     */
    public function addStockBatch(Product $product, array $data): Stock
    {
        return DB::transaction(function () use ($product, $data) {
            $batch = Stock::create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'batch_no' => $data['batch_number'] ?? $this->generateBatchNumber(),
                'expire_date' => $data['expiry_date'] ?? null,
                'productStock' => (float) ($data['quantity'] ?? 0),
                'purchase_price' => (float) ($data['purchase_price'] ?? 0),
            ]);

            // تسجيل حركة المخزون
            \App\Models\StockMovement::create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'batch_no' => $batch->batch_no,
                'movement_type' => 'in',
                'quantity' => (float) ($data['quantity'] ?? 0),
                'reference_type' => 'product',
                'reference_id' => $product->id,
                'notes' => 'إضافة دفعة للمنتج',
                'created_by' => Auth::id(),
            ]);

            return $batch;
        });
    }

    /**
     * Get all stock batches for a product (FEFO ordered).
     */
    public function getStockBatches(Product $product): \Illuminate\Support\Collection
    {
        return $product->stocks()
            ->where('productStock', '>', 0)
            ->orderBy('expire_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    // ────────────────────────────── Variants ──────────────────────────────

    /**
     * Create product variants.
     */
    protected function createVariants(Product $product, array $variants, string $companyId): void
    {
        foreach ($variants as $variant) {
            $variantName = $variant['name'] ?? $variant['variant_name'] ?? '';
            if (empty($variantName)) continue;

            ProductVariant::create([
                'company_id' => $companyId,
                'product_id' => $product->id,
                'variant_name' => $variantName,
                'barcode' => $variant['barcode'] ?? null,
                'package_size' => $variant['package_size'] ?? null,
                'unit_quantity' => $variant['unit_quantity'] ?? 1,
                'sales_price' => $variant['sales_price'] ?? $product->sales_price,
                'purchase_price' => $variant['purchase_price'] ?? $product->purchase_price,
                'is_default' => $variant['is_default'] ?? false,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Sync variants (update/create/delete as needed).
     */
    protected function syncVariants(Product $product, array $variants): void
    {
        $existingIds = $product->variants()->pluck('id')->toArray();
        $updatedIds = [];

        foreach ($variants as $variant) {
            if (!empty($variant['id']) && in_array($variant['id'], $existingIds)) {
                ProductVariant::where('id', $variant['id'])->update([
                    'variant_name' => $variant['name'] ?? $variant['variant_name'] ?? '',
                    'barcode' => $variant['barcode'] ?? null,
                    'package_size' => $variant['package_size'] ?? null,
                    'unit_quantity' => $variant['unit_quantity'] ?? 1,
                    'sales_price' => $variant['sales_price'] ?? 0,
                    'is_default' => $variant['is_default'] ?? false,
                ]);
                $updatedIds[] = $variant['id'];
            } else {
                $new = ProductVariant::create([
                    'company_id' => $product->company_id,
                    'product_id' => $product->id,
                    'variant_name' => $variant['name'] ?? $variant['variant_name'] ?? '',
                    'barcode' => $variant['barcode'] ?? null,
                    'package_size' => $variant['package_size'] ?? null,
                    'unit_quantity' => $variant['unit_quantity'] ?? 1,
                    'sales_price' => $variant['sales_price'] ?? $product->sales_price,
                    'is_default' => $variant['is_default'] ?? false,
                    'is_active' => true,
                ]);
                $updatedIds[] = $new->id;
            }
        }

        $toDelete = array_diff($existingIds, $updatedIds);
        if (!empty($toDelete)) {
            ProductVariant::whereIn('id', $toDelete)->delete();
        }
    }

    // ────────────────────────────── Images ──────────────────────────────

    /**
     * Handle image removals.
     */
    protected function handleImageRemovals(Product $product, array $data): void
    {
        if (!empty($data['removed_images'])) {
            foreach ((array) $data['removed_images'] as $image) {
                if (Storage::exists($image)) {
                    Storage::delete($image);
                }
            }
        }
    }

    /**
     * Merge new images with existing ones.
     */
    protected function mergeImages(Product $product, array $data): array
    {
        if (!empty($data['images'])) {
            $newImages = is_array($data['images']) ? $data['images'] : [$data['images']];
            return array_merge($product->images ?? [], $newImages);
        }
        return $product->images ?? [];
    }

    // ────────────────────────────── Drug Interactions ──────────────────────────────

    /**
     * Check dangerous interactions between a set of products.
     */
    public function checkDrugInteractions(array $productIds): array
    {
        if (count($productIds) < 2) {
            return [];
        }

        $interactions = DrugInteraction::where('is_active', true)
            ->whereIn('interaction_level', ['severe', 'contraindicated'])
            ->where(function ($q) use ($productIds) {
                $q->whereIn('product_a_id', $productIds)
                    ->whereIn('product_b_id', $productIds);
            })
            ->with(['productA:id,name,generic_name,brand_name', 'productB:id,name,generic_name,brand_name'])
            ->get();

        return $interactions->map(fn($i) => [
            'level' => $i->interaction_level,
            'severity_label' => $i->interaction_level === 'contraindicated' ? 'ممنوع تماماً' : 'خطير',
            'product_a' => [
                'id' => $i->productA->id,
                'name' => $i->productA->name ?? $i->productA->generic_name,
            ],
            'product_b' => [
                'id' => $i->productB->id,
                'name' => $i->productB->name ?? $i->productB->generic_name,
            ],
            'description' => $i->description,
            'clinical_effects' => $i->clinical_effects,
            'management' => $i->management,
        ])->toArray();
    }

    /**
     * Check interactions for a single product against all known interactions.
     */
    public function checkInteractionsForProduct(string $productId): array
    {
        $interactions = DrugInteraction::where('is_active', true)
            ->whereIn('interaction_level', ['severe', 'contraindicated'])
            ->where(fn($q) => $q->where('product_a_id', $productId)->orWhere('product_b_id', $productId))
            ->with(['productA:id,name,generic_name,brand_name', 'productB:id,name,generic_name,brand_name'])
            ->get();

        return $interactions->map(function ($i) use ($productId) {
            $other = $i->product_a_id === $productId ? $i->productB : $i->productA;
            return [
                'with_product' => ['id' => $other->id, 'name' => $other->name ?? $other->generic_name],
                'level' => $i->interaction_level,
                'description' => $i->description,
                'management' => $i->management,
            ];
        })->toArray();
    }

    // ────────────────────────────── Import/Export ──────────────────────────────

    /**
     * Bulk import products from data array.
     */
    public function importProducts(array $rows, string $companyId, int $userId): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $companyId, $userId, &$imported, &$failed, &$errors) {
            foreach ($rows as $index => $row) {
                try {
                    $validator = Validator::make($row, [
                        'generic_name' => 'required|string|max:255',
                        'barcode' => 'nullable|string|max:100',
                        'sales_price' => 'required|numeric|min:0',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = 'صف ' . ($index + 1) . ': ' . implode(', ', $validator->errors()->all());
                        $failed++;
                        continue;
                    }

                    $data = array_merge($row, [
                        'company_id' => $companyId,
                        'is_active' => true,
                    ]);

                    if (!empty($row['barcode'])) {
                        Product::updateOrCreate(
                            ['company_id' => $companyId, 'barcode' => $row['barcode']],
                            $data
                        );
                    } else {
                        Product::create($data);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = 'صف ' . ($index + 1) . ': ' . $e->getMessage();
                    $failed++;
                }
            }
        });

        return compact('imported', 'failed', 'errors');
    }

    /**
     * Export products to array format.
     */
    public function exportProducts(array $filters = []): array
    {
        $query = Product::withCommon()->withStock();

        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }
        if (!empty($filters['is_active'])) {
            $query->active();
        }

        return $query->get()->map(fn($p) => [
            'name' => $p->name,
            'generic_name' => $p->generic_name,
            'brand_name' => $p->brand_name,
            'barcode' => $p->barcode,
            'code' => $p->product_code,
            'dosage_form' => $p->dosage_form,
            'strength' => $p->strength,
            'sales_price' => (float) $p->sales_price,
            'purchase_price' => (float) $p->purchase_price,
            'wholesale_price' => (float) $p->wholesale_price,
            'stock' => (float) $p->current_stock,
            'min_stock' => (float) $p->min_stock,
            'reorder_level' => (float) $p->reorder_level,
            'prescription_required' => $p->prescription_required ? 'yes' : 'no',
            'is_controlled' => $p->is_controlled ? 'yes' : 'no',
            'is_active' => $p->is_active ? 'yes' : 'no',
            'category' => $p->category?->name,
            'manufacturer' => $p->manufacturer?->name,
            'created_at' => $p->created_at?->format('Y-m-d'),
        ])->toArray();
    }

    // ────────────────────────────── Data Helpers ──────────────────────────────

    /**
     * Prepare product data for creation.
     */
    protected function prepareProductData(array $data, string $companyId, ?int $userId): array
    {
        return [
            'company_id' => $companyId,
            'branch_id' => $data['branch_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'manufacturer_id' => $data['manufacturer_id'] ?? null,
            'unit_id' => $data['unit_id'] ?? null,

            'name' => $data['name'] ?? $data['product_name'] ?? $data['generic_name'] ?? null,
            'generic_name' => $data['generic_name'] ?? null,
            'brand_name' => $data['brand_name'] ?? null,
            'product_name' => $data['product_name'] ?? $data['generic_name'] ?? null,

            'product_code' => $data['product_code'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'internal_code' => $data['internal_code'] ?? null,
            'atc_code' => $data['atc_code'] ?? null,

            'dosage_form' => $data['dosage_form'] ?? null,
            'strength' => $data['strength'] ?? null,
            'unit_of_measure' => $data['unit_of_measure'] ?? 'piece',
            'pack_size' => (int) ($data['pack_size'] ?? 1),
            'pack_unit' => $data['pack_unit'] ?? null,

            'prescription_required' => filter_var($data['prescription_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_controlled' => filter_var($data['is_controlled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'controlled_substance_schedule' => $data['controlled_substance_schedule'] ?? null,
            'requires_special_handling' => filter_var($data['requires_special_handling'] ?? false, FILTER_VALIDATE_BOOLEAN),

            'storage_conditions' => $data['storage_conditions'] ?? null,
            'shelf_life_months' => $data['shelf_life_months'] ?? null,

            'is_splittable' => filter_var($data['is_splittable'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'split_unit_id' => $data['split_unit_id'] ?? null,
            'split_quantity' => $data['split_quantity'] ?? null,

            'min_stock' => (float) ($data['min_stock'] ?? 0),
            'max_stock' => (float) ($data['max_stock'] ?? 0),
            'reorder_level' => (float) ($data['reorder_level'] ?? 0),
            'alert_qty' => (float) ($data['alert_qty'] ?? 0),

            'purchase_price' => (float) ($data['purchase_price'] ?? 0),
            'sales_price' => (float) ($data['sales_price'] ?? 0),
            'selling_price' => (float) ($data['selling_price'] ?? $data['sales_price'] ?? 0),
            'wholesale_price' => (float) ($data['wholesale_price'] ?? 0),
            'cost_price' => (float) ($data['cost_price'] ?? 0),
            'price_currency' => $data['price_currency'] ?? 'EGP',
            'tax_id' => $data['tax_id'] ?? null,
            'tax_rate_id' => $data['tax_rate_id'] ?? null,
            'tax_rate' => (float) ($data['tax_rate'] ?? 0),
            'discount_percentage' => (float) ($data['discount_percentage'] ?? 0),

            'description' => $data['description'] ?? null,
            'usage_instructions' => $data['usage_instructions'] ?? null,
            'side_effects' => $data['side_effects'] ?? null,

            'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'is_taxable' => filter_var($data['is_taxable'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'track_inventory' => filter_var($data['track_inventory'] ?? true, FILTER_VALIDATE_BOOLEAN),

            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }

    /**
     * Filter only allowed fields for update.
     */
    protected function filterUpdatableFields(array $data): array
    {
        $allowed = [
            'name', 'generic_name', 'brand_name', 'product_name',
            'product_code', 'barcode', 'internal_code', 'atc_code',
            'category_id', 'manufacturer_id', 'unit_id',
            'dosage_form', 'strength', 'unit_of_measure', 'pack_size', 'pack_unit',
            'prescription_required', 'is_controlled', 'controlled_substance_schedule',
            'requires_special_handling', 'storage_conditions', 'shelf_life_months',
            'is_splittable', 'split_unit_id', 'split_quantity',
            'min_stock', 'max_stock', 'reorder_level', 'alert_qty',
            'purchase_price', 'purchase_without_tax', 'purchase_with_tax',
            'sales_price', 'selling_price', 'wholesale_price', 'cost_price', 'price_currency',
            'tax_id', 'tax_rate_id', 'tax_rate', 'discount_percentage',
            'description', 'usage_instructions', 'side_effects',
            'images', 'product_image', 'image_path', 'meta', 'metadata',
            'is_active', 'is_taxable', 'track_inventory',
        ];

        return array_intersect_key($data, array_flip($allowed));
    }

    /**
     * Record a price change in history.
     */
    public function recordPriceChange(
        Product $product,
        string $type,
        float $oldPrice,
        float $newPrice,
        string $reason = 'manual_update'
    ): ProductPriceHistory {
        return ProductPriceHistory::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'price_type' => $type,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'change_reason' => $reason,
            'changed_by' => Auth::id(),
            'effective_date' => now(),
        ]);
    }

    /**
     * Generate unique batch number.
     */
    public function generateBatchNumber(): string
    {
        return 'BATCH-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
    }

    // ────────────────────────────── Legacy Compatibility ──────────────────────────────

    /**
     * Legacy index method for Acnoo controller.
     */
    public function index(array $filters = [])
    {
        $query = Product::query()
            ->with([
                'unit:id,unitName', 'manufacturer:id,name',
                'box_size:id,name', 'category:id,categoryName',
                'stocks:id,expire_date,product_id,batch_no,productStock',
                'tax:id,rate',
            ])
            ->withSum('stocks', 'productStock');

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->latest()->paginate();
    }

    /**
     * Legacy store method for Acnoo controller.
     */
    public function store(array $data)
    {
        return $this->create($data);
    }

    /**
     * Legacy update stock method for Acnoo controller.
     */
    public function updateStock(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update([
                'purchase_without_tax' => $data['purchase_without_tax'] ?? $product->purchase_without_tax,
                'purchase_with_tax' => $data['purchase_with_tax'] ?? $product->purchase_with_tax,
                'profit_percent' => $data['profit_percent'] ?? $product->profit_percent,
                'sales_price' => $data['sales_price'] ?? $product->sales_price,
                'wholesale_price' => $data['wholesale_price'] ?? $product->wholesale_price,
            ]);

            if (!empty($data['qty'])) {
                $this->addStockBatch($product, [
                    'batch_number' => $data['batch_no'] ?? $this->generateBatchNumber(),
                    'expiry_date' => $data['expire_date'] ?? null,
                    'quantity' => (float) $data['qty'],
                    'purchase_price' => (float) ($data['purchase_price'] ?? $product->purchase_price),
                ]);
            }

            return $product->fresh();
        });
    }

    /**
     * Legacy stocks with product listing.
     */
    public function stocksWithProduct(array $filters = []): \Illuminate\Support\Collection
    {
        return Product::query()
            ->with(['stocks:id,product_id,batch_no,expire_date,productStock'])
            ->withSum('stocks', 'productStock')
            ->when(!empty($filters['search']), fn($q) => $q->search($filters['search']))
            ->when(!empty($filters['check_stock']), fn($q) => $q->whereHas('stocks', fn($sq) => $sq->where('productStock', '>', 0)))
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'productName' => $product->name ?? $product->generic_name,
                'productCode' => $product->product_code,
                'barcode' => $product->barcode,
                'brand_name' => $product->brand_name,
                'sales_price' => $product->sales_price,
                'stocks' => $product->stocks,
                'productStock' => $product->stocks_sum_productStock ?? 0,
            ]);
    }

    /**
     * Egyptian drug database import (legacy).
     */
    public function importEgyptianDrugDatabase(string $csvPath): int
    {
        $imported = 0;
        $data = array_map('str_getcsv', file($csvPath));

        DB::transaction(function () use (&$imported, $data) {
            foreach ($data as $index => $row) {
                if ($index === 0) continue;

                Product::updateOrCreate(
                    ['barcode' => $row[0] ?? null],
                    [
                        'generic_name' => $row[1] ?? null,
                        'brand_name' => $row[2] ?? null,
                        'strength' => $row[3] ?? null,
                        'dosage_form' => $row[4] ?? null,
                        'sales_price' => $row[7] ?? 0,
                        'is_active' => true,
                    ]
                );
                $imported++;
            }
        });

        return $imported;
    }
}
