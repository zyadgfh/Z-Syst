<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\DrugInteraction;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Enhanced Product Service for Pharmacy Management
 * 
 * Handles all product-related operations including:
 * - CRUD operations
 * - Advanced search (name, barcode, generic, fuzzy)
 * - Price history tracking
 * - Drug interaction checking
 * - Egyptian drug database import
 */
class ProductService
{
    /**
     * Search products with advanced filters.
     */
    public function search(array $filters = []): LengthAwarePaginator
    {
        $query = Product::with([
            'category:id,name',
            'manufacturer:id,name',
            'tax:id,name,rate',
            'variants:id,product_id,variant_name,barcode,sales_price',
        ])
            ->withSum('inventory', 'quantity');

        // Text search (name, barcode, generic, brand)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('generic_name', 'ilike', "%{$search}%")
                    ->orWhere('brand_name', 'ilike', "%{$search}%")
                    ->orWhere('product_name', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%")
                    ->orWhere('product_code', 'ilike', "%{$search}%");
            });
        }

        // Barcode exact match
        if (!empty($filters['barcode'])) {
            $query->where('barcode', $filters['search']);
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Manufacturer filter
        if (!empty($filters['manufacturer_id'])) {
            $query->where('manufacturer_id', $filters['manufacturer_id']);
        }

        // Dosage form filter
        if (!empty($filters['dosage_form'])) {
            $query->where('dosage_form', $filters['dosage_form']);
        }

        // Controlled substance filter
        if (isset($filters['controlled_substance'])) {
            $query->whereNotNull('controlled_substance_schedule');
        }

        // Prescription required filter
        if (isset($filters['prescription_required'])) {
            $query->where('prescription_required', $filters['prescription_required']);
        }

        // Low stock filter
        if (!empty($filters['low_stock'])) {
            $query->whereRaw('COALESCE((SELECT SUM(quantity) FROM inventory WHERE product_id = products.id), 0) <= reorder_level');
        }

        // Expired products filter
        if (!empty($filters['expired'])) {
            $query->whereHas('inventory', function ($q) {
                $q->where('expiry_date', '<', now()->toDateString())
                    ->where('quantity', '>', 0);
            });
        }

        // Expiring soon filter
        if (!empty($filters['expiring_soon'])) {
            $days = (int) ($filters['expiring_soon_days'] ?? 90);
            $query->whereHas('inventory', function ($q) use ($days) {
                $q->whereBetween('expiry_date', [now(), now()->addDays($days)])
                    ->where('quantity', '>', 0);
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get product by barcode.
     */
    public function getByBarcode(string $barcode): ?Product
    {
        return Product::where('barcode', $barcode)
            ->with(['category', 'manufacturer', 'variants', 'inventory' => function ($q) {
                $q->where('quantity', '>', 0)
                    ->orderBy('expiry_date', 'asc');
            }])
            ->first();
    }

    /**
     * Create a new product.
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $companyId = $data['company_id'] ?? app('tenant.company_id');
            $userId = Auth::id();

            $product = Product::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'manufacturer_id' => $data['manufacturer_id'] ?? null,
                
                // Names
                'generic_name' => $data['generic_name'] ?? null,
                'brand_name' => $data['brand_name'] ?? null,
                'product_name' => $data['product_name'] ?? $data['generic_name'] ?? null,
                
                // Identification
                'product_code' => $data['product_code'] ?? $this->generateProductCode(),
                'barcode' => $data['barcode'] ?? null,
                'internal_code' => $data['internal_code'] ?? null,
                
                // Pharmacy Specific
                'dosage_form' => $data['dosage_form'] ?? null,
                'strength' => $data['strength'] ?? null,
                'unit_of_measure' => $data['unit_of_measure'] ?? null,
                'package_size' => $data['package_size'] ?? null,
                
                // Prescription & Control
                'prescription_required' => $data['prescription_required'] ?? false,
                'controlled_substance_schedule' => $data['controlled_substance_schedule'] ?? null,
                'requires_special_handling' => $data['requires_special_handling'] ?? false,
                
                // Storage
                'storage_conditions' => $data['storage_conditions'] ?? null,
                'shelf_life_months' => $data['shelf_life_months'] ?? null,
                
                // Stock Management
                'min_stock' => $data['min_stock'] ?? 0,
                'max_stock' => $data['max_stock'] ?? 0,
                'reorder_level' => $data['reorder_level'] ?? 0,
                'alert_qty' => $data['alert_qty'] ?? 0,
                
                // Pricing
                'purchase_price' => $data['purchase_price'] ?? 0,
                'purchase_without_tax' => $data['purchase_without_tax'] ?? 0,
                'purchase_with_tax' => $data['purchase_with_tax'] ?? 0,
                'sales_price' => $data['sales_price'] ?? 0,
                'wholesale_price' => $data['wholesale_price'] ?? 0,
                'cost_price' => $data['cost_price'] ?? 0,
                'price_currency' => $data['price_currency'] ?? 'EGP',
                'tax_id' => $data['tax_id'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? 0,
                
                // Description
                'description' => $data['description'] ?? null,
                'usage_instructions' => $data['usage_instructions'] ?? null,
                'side_effects' => $data['side_effects'] ?? null,
                
                // Status
                'is_active' => $data['is_active'] ?? true,
                'is_taxable' => $data['is_taxable'] ?? true,
                'track_inventory' => $data['track_inventory'] ?? true,
                
                // Audit
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Create initial inventory if batch/qty provided
            if (!empty($data['batch_number']) || !empty($data['quantity'])) {
                $this->createInventory($product, [
                    'batch_number' => $data['batch_number'] ?? $this->generateBatchNumber(),
                    'expiry_date' => $data['expiry_date'] ?? null,
                    'quantity' => $data['quantity'] ?? 0,
                    'purchase_price' => $data['purchase_price'] ?? $product->purchase_price,
                ]);
            }

            // Create product variants if provided
            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $variant) {
                    ProductVariant::create([
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                        'variant_name' => $variant['name'],
                        'barcode' => $variant['barcode'] ?? null,
                        'package_size' => $variant['package_size'] ?? null,
                        'unit_quantity' => $variant['unit_quantity'] ?? 1,
                        'sales_price' => $variant['sales_price'] ?? $product->sales_price,
                        'is_default' => $variant['is_default'] ?? false,
                    ]);
                }
            }

            // Record price history if price changed
            if (isset($data['sales_price'])) {
                $this->recordPriceChange($product, 'sales', 0, $data['sales_price'], 'initial_setup');
            }

            return $product->fresh();
        });
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $userId = Auth::id();
            $oldPrice = $product->sales_price;

            // Handle images
            if (!empty($data['removed_images'])) {
                foreach ($data['removed_images'] as $image) {
                    if (Storage::exists($image)) {
                        Storage::delete($image);
                    }
                }
            }

            $mergedImages = array_merge(
                $product->images ?? [],
                $data['images'] ?? []
            );

            $product->update([
                'category_id' => $data['category_id'] ?? $product->category_id,
                'manufacturer_id' => $data['manufacturer_id'] ?? $product->manufacturer_id,
                
                'generic_name' => $data['generic_name'] ?? $product->generic_name,
                'brand_name' => $data['brand_name'] ?? $product->brand_name,
                'product_name' => $data['product_name'] ?? $product->generic_name,
                
                'product_code' => $data['product_code'] ?? $product->product_code,
                'barcode' => $data['barcode'] ?? $product->barcode,
                
                'dosage_form' => $data['dosage_form'] ?? $product->dosage_form,
                'strength' => $data['strength'] ?? $product->strength,
                'unit_of_measure' => $data['unit_of_measure'] ?? $product->unit_of_measure,
                
                'prescription_required' => $data['prescription_required'] ?? $product->prescription_required,
                'controlled_substance_schedule' => $data['controlled_substance_schedule'] ?? $product->controlled_substance_schedule,
                
                'min_stock' => $data['min_stock'] ?? $product->min_stock,
                'max_stock' => $data['max_stock'] ?? $product->max_stock,
                'reorder_level' => $data['reorder_level'] ?? $product->reorder_level,
                
                'sales_price' => $data['sales_price'] ?? $product->sales_price,
                'wholesale_price' => $data['wholesale_price'] ?? $product->wholesale_price,
                'tax_rate' => $data['tax_rate'] ?? $product->tax_rate,
                
                'images' => $mergedImages,
                'updated_by' => $userId,
            ]);

            // Record price history if price changed
            if (isset($data['sales_price']) && $oldPrice != $data['sales_price']) {
                $this->recordPriceChange($product, 'sales', $oldPrice, $data['sales_price'], 'price_update');
            }

            return $product->fresh();
        });
    }

    /**
     * Delete a product (soft delete).
     */
    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Delete associated images
            foreach ($product->images ?? [] as $image) {
                if (Storage::exists($image)) {
                    Storage::delete($image);
                }
            }

            return $product->delete();
        });
    }

    /**
     * Check drug interactions for a set of products.
     */
    public function checkDrugInteractions(array $productIds): array
    {
        $interactions = [];

        foreach ($productIds as $productId) {
            $dangerousInteractions = DrugInteraction::where(function ($query) use ($productId) {
                $query->where('product_a_id', $productId)
                    ->orWhere('product_b_id', $productId);
            })
                ->whereIn('interaction_level', ['severe', 'contraindicated'])
                ->with(['productA', 'productB'])
                ->get();

            foreach ($dangerousInteractions as $interaction) {
                $otherProductId = $interaction->product_a_id === $productId 
                    ? $interaction->product_b_id 
                    : $interaction->product_a_id;

                $interactions[] = [
                    'product_id' => $productId,
                    'interacts_with' => $otherProductId,
                    'level' => $interaction->interaction_level,
                    'description' => $interaction->description,
                    'clinical_effects' => $interaction->clinical_effects,
                    'management' => $interaction->management,
                ];
            }
        }

        return $interactions;
    }

    /**
     * Import Egyptian drug database.
     */
    public function importEgyptianDrugDatabase(string $csvPath): int
    {
        $imported = 0;
        $data = array_map('str_getcsv', file($csvPath));
        
        DB::transaction(function () use (&$imported, $data) {
            foreach ($data as $index => $row) {
                if ($index === 0) continue; // Skip header
                
                $product = Product::updateOrCreate(
                    ['barcode' => $row[0] ?? null],
                    [
                        'generic_name' => $row[1] ?? null,
                        'brand_name' => $row[2] ?? null,
                        'strength' => $row[3] ?? null,
                        'dosage_form' => $row[4] ?? null,
                        'manufacturer_id' => $this->getOrCreateManufacturer($row[5] ?? null),
                        'purchase_price' => $row[6] ?? 0,
                        'sales_price' => $row[7] ?? 0,
                        'is_active' => true,
                    ]
                );
                
                $imported++;
            }
        });
        
        return $imported;
    }

    /**
     * Create inventory record for a product.
     */
    protected function createInventory(Product $product, array $data): Inventory
    {
        $companyId = app('tenant.company_id');
        
        // Apply FEFO logic - use earliest expiry first
        return Inventory::create([
            'company_id' => $companyId,
            'branch_id' => $product->branch_id,
            'product_id' => $product->id,
            'batch_number' => $data['batch_number'],
            'expiry_date' => $data['expiry_date'],
            'quantity' => $data['quantity'],
            'purchase_price' => $data['purchase_price'],
            'location' => $data['location'] ?? null,
        ]);
    }

    /**
     * Record price change for history.
     */
    protected function recordPriceChange(
        Product $product, 
        string $type, 
        float $oldPrice, 
        float $newPrice, 
        string $reason
    ): ProductPriceHistory {
        $userId = Auth::id();
        
        return ProductPriceHistory::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'price_type' => $type,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'change_reason' => $reason,
            'changed_by' => $userId,
        ]);
    }

    /**
     * Generate unique product code.
     */
    protected function generateProductCode(): string
    {
        return 'PRD-' . strtoupper(uniqid());
    }

    /**
     * Generate unique batch number.
     */
    protected function generateBatchNumber(): string
    {
        return 'BATCH-' . now()->format('ymd') . '-' . strtoupper(uniqid());
    }

    /**
     * Get or create manufacturer.
     */
    protected function getOrCreateManufacturer(?string $name): ?int
    {
        if (!$name) {
            return null;
        }

        $manufacturer = Manufacturer::where('name', $name)->first();
        
        if ($manufacturer) {
            return $manufacturer->id;
        }

        return Manufacturer::create([
            'company_id' => app('tenant.company_id'),
            'name' => $name,
            'is_active' => true,
        ])->id;
    }
}