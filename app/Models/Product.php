<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // Identity
        'productName',
        'business_id',
        'sku',
        'internal_code',
        'scientific_name',
        'commercial_name',
        'short_name',
        'description',
        'notes',
        'barcode',
        'barcode_type',
        'gtin',
        'productCode',

        // Classification
        'category_id',
        'subcategory_id',
        'brand_id',
        'manufacturer_id',
        'type_id',
        'box_size_id',
        'product_type',
        'dosage_form',
        'route_of_administration',
        'strength',
        'unit_type',

        // Units
        'unit_id',
        'purchase_unit_id',
        'sales_unit_id',
        'conversion_factor',
        'allow_fractional_quantity',

        // Pricing
        'purchase_without_tax',
        'purchase_with_tax',
        'profit_percent',
        'sales_price',
        'wholesale_price',
        'minimum_selling_price',
        'special_price',
        'tax_included',
        'tax_id',
        'tax_type',

        // Inventory
        'alert_qty',
        'track_inventory',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'reorder_quantity',
        'safety_stock',
        'allow_negative_stock',
        'stock_status',

        // Pharmacy
        'active_ingredient',
        'concentration',
        'dosage',
        'package_size',
        'package_unit',
        'prescription_required',
        'controlled_item',
        'refrigerated',
        'temperature_requirements',
        'storage_instructions',

        // Expiration
        'track_expiration',
        'minimum_remaining_shelf_life',
        'expiration_warning_days',

        // Supplier
        'preferred_supplier_id',

        // Status
        'active',
        'discontinued',
        'archived',

        // Audit
        'created_by',
        'updated_by',
        'branch_id',

        // Existing
        'images',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
        'images' => 'json',
        'tax_included' => 'boolean',
        'track_inventory' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'allow_fractional_quantity' => 'boolean',
        'prescription_required' => 'boolean',
        'controlled_item' => 'boolean',
        'refrigerated' => 'boolean',
        'track_expiration' => 'boolean',
        'active' => 'boolean',
        'discontinued' => 'boolean',
        'archived' => 'boolean',
        'conversion_factor' => 'decimal:4',
        'minimum_remaining_shelf_life' => 'integer',
        'expiration_warning_days' => 'integer',
    ];

    // ── Boot ──

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (auth()->check()) {
                $product->created_by = auth()->id();
                $product->updated_by = auth()->id();
            }
        });

        static::updating(function ($product) {
            if (auth()->check()) {
                $product->updated_by = auth()->id();
            }
        });
    }

    // ── Relationships ──

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class)->where('productStock', '>', 0);
    }

    public function allStocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get stocks ordered by FEFO (nearest expiry first).
     */
    public function fefoStocks(): HasMany
    {
        return $this->hasMany(Stock::class)
            ->where('productStock', '>', 0)
            ->orderByRaw('CASE WHEN expire_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expire_date', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Get only expiring stocks (within grace days).
     */
    public function expiringStocks(int $graceDays = 30): HasMany
    {
        $threshold = now()->startOfDay()->addDays($graceDays);

        return $this->hasMany(Stock::class)
            ->where('productStock', '>', 0)
            ->whereNotNull('expire_date')
            ->where('expire_date', '<=', $threshold)
            ->where('expire_date', '>=', now()->startOfDay())
            ->orderBy('expire_date', 'asc');
    }

    public function expiringItem(): HasOne
    {
        return $this->hasOne(Stock::class, 'product_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'sales_unit_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'brand_id');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function medicine_type(): BelongsTo
    {
        return $this->belongsTo(MedicineType::class, 'type_id');
    }

    public function box_size(): BelongsTo
    {
        return $this->belongsTo(BoxSize::class);
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'preferred_supplier_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get barcodes for the product.
     */
    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class);
    }

    /**
     * Get active barcodes for the product.
     */
    public function activeBarcodes(): HasMany
    {
        return $this->hasMany(Barcode::class)->where('is_active', true);
    }

    /**
     * Get supplier relationships for this product.
     */
    public function itemSuppliers(): HasMany
    {
        return $this->hasMany(ItemSupplier::class);
    }

    /**
     * Get suppliers through pivot.
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'item_suppliers', 'product_id', 'supplier_id')
            ->withPivot([
                'supplier_item_code', 'supplier_barcode', 'supplier_purchase_price',
                'supplier_currency', 'lead_time_days', 'minimum_order_quantity',
                'is_preferred', 'notes',
            ])
            ->withTimestamps();
    }

    /**
     * Get sale details for this product.
     */
    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetails::class);
    }

    /**
     * Get purchase details for this product.
     */
    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetails::class);
    }

    /**
     * Get stock movements for this product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ── Scopes ──

    /**
     * Scope: Products that have batches expiring within a given number of days.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereHas('stocks', function ($q) use ($days) {
            $q->whereNotNull('expire_date')
                ->where('expire_date', '<=', now()->startOfDay()->addDays($days))
                ->where('expire_date', '>=', now()->startOfDay())
                ->where('productStock', '>', 0);
        });
    }

    /**
     * Scope: Products that have expired stock.
     */
    public function scopeHasExpired($query)
    {
        return $query->whereHas('stocks', function ($q) {
            $q->whereNotNull('expire_date')
                ->where('expire_date', '<', now()->startOfDay())
                ->where('productStock', '>', 0);
        });
    }

    /**
     * Scope: Active products only.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true)->where('archived', false);
    }

    /**
     * Scope: Products with low stock.
     */
    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
            ->whereColumn('alert_qty', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(productStock), 0) FROM stocks WHERE stocks.product_id = products.id AND stocks.productStock > 0) <= products.alert_qty');
    }

    /**
     * Scope: Out of stock products.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('track_inventory', true)
            ->whereRaw('(SELECT COALESCE(SUM(productStock), 0) FROM stocks WHERE stocks.product_id = products.id AND stocks.productStock > 0) = 0');
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Filter by brand.
     */
    public function scopeByBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Scope: Filter by manufacturer.
     */
    public function scopeByManufacturer($query, $manufacturerId)
    {
        return $query->where('manufacturer_id', $manufacturerId);
    }

    /**
     * Scope: Prescription required.
     */
    public function scopePrescriptionRequired($query)
    {
        return $query->where('prescription_required', true);
    }

    /**
     * Scope: Filter by stock status.
     */
    public function scopeByStockStatus($query, string $status)
    {
        return match ($status) {
            'in_stock' => $query->where('stock_status', 'in_stock'),
            'low_stock' => $query->where('stock_status', 'low_stock'),
            'out_of_stock' => $query->where('stock_status', 'out_of_stock'),
            default => $query,
        };
    }

    /**
     * Scope: Search by term across multiple fields.
     */
    public function scopeSearch($query, string $term)
    {
        $like = "%{$term}%";
        return $query->where(function ($q) use ($like) {
            $q->where('productName', 'like', $like)
                ->orWhere('scientific_name', 'like', $like)
                ->orWhere('commercial_name', 'like', $like)
                ->orWhere('barcode', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('productCode', 'like', $like)
                ->orWhere('internal_code', 'like', $like)
                ->orWhere('gtin', 'like', $like)
                ->orWhereHas('manufacturer', function ($mq) use ($like) {
                    $mq->where('name', 'like', $like);
                });
        });
    }

    // ── Accessors ──

    /**
     * Get the total stock across all batches.
     */
    public function getTotalStockAttribute(): int
    {
        return (int) $this->allStocks()->sum('productStock');
    }

    /**
     * Get stock count for the main stocks relation (with productStock > 0).
     */
    public function getStockSumAttribute()
    {
        return $this->stocks()->sum('productStock');
    }

    /**
     * Get current selling price (special price if set, otherwise regular).
     */
    public function getCurrentSellingPriceAttribute(): float
    {
        if ($this->special_price !== null && $this->special_price > 0) {
            return (float) $this->special_price;
        }
        return (float) $this->sales_price;
    }

    /**
     * Calculate gross profit margin percentage.
     */
    public function getProfitMarginAttribute(): float
    {
        $cost = $this->purchase_with_tax ?: $this->purchase_without_tax;
        if ($cost <= 0) {
            return 0;
        }
        $price = $this->getCurrentSellingPriceAttribute();
        return round((($price - $cost) / $cost) * 100, 2);
    }

    /**
     * Get total sold quantity.
     */
    public function getTotalSoldAttribute(): int
    {
        return (int) $this->saleDetails()->sum('quantities');
    }

    /**
     * Get total purchased quantity.
     */
    public function getTotalPurchasedAttribute(): int
    {
        return (int) $this->purchaseDetails()->sum('quantities');
    }

    /**
     * Determine stock status dynamically.
     */
    public function getStockStatusDisplayAttribute(): string
    {
        $totalStock = $this->getTotalStockAttribute();

        if ($totalStock <= 0) {
            return 'out_of_stock';
        }

        if ($this->alert_qty > 0 && $totalStock <= $this->alert_qty) {
            return 'low_stock';
        }

        if ($this->reorder_point > 0 && $totalStock <= $this->reorder_point) {
            return 'reorder';
        }

        return 'in_stock';
    }

    // ── Methods ──

    /**
     * Check if product has related transactions (sales, purchases, etc.).
     */
    public function hasTransactions(): bool
    {
        return $this->saleDetails()->exists()
            || $this->purchaseDetails()->exists()
            || $this->stockMovements()->exists();
    }

    /**
     * Check if the product can be safely deleted (no transactions).
     */
    public function canBeDeleted(): bool
    {
        return !$this->hasTransactions();
    }

    /**
     * Update the stock status based on current stock levels.
     */
    public function updateStockStatus(): void
    {
        $totalStock = $this->getTotalStockAttribute();

        $status = match (true) {
            $totalStock <= 0 => 'out_of_stock',
            $this->alert_qty > 0 && $totalStock <= $this->alert_qty => 'low_stock',
            $this->reorder_point > 0 && $totalStock <= $this->reorder_point => 'reorder',
            default => 'in_stock',
        };

        $this->update(['stock_status' => $status]);
    }

    /**
     * Check if a duplicate exists with the same barcode, SKU, or name.
     */
    public static function findDuplicates(array $data, ?int $excludeId = null): array
    {
        $query = static::query();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $duplicates = [];

        // Check barcode
        if (!empty($data['barcode'])) {
            $existing = (clone $query)->where('barcode', $data['barcode'])->first();
            if ($existing) {
                $duplicates['barcode'] = $existing;
            }
        }

        // Check SKU / productCode
        $sku = $data['sku'] ?? $data['productCode'] ?? null;
        if (!empty($sku)) {
            $existing = (clone $query)->where('sku', $sku)->orWhere('productCode', $sku)->first();
            if ($existing) {
                $duplicates['sku'] = $existing;
            }
        }

        // Check scientific name (normalized)
        if (!empty($data['scientific_name'])) {
            $existing = (clone $query)->whereRaw('LOWER(scientific_name) = ?', [strtolower($data['scientific_name'])])->first();
            if ($existing) {
                $duplicates['scientific_name'] = $existing;
            }
        }

        // Check name (soft duplicate — warn only)
        if (!empty($data['productName'])) {
            $existing = (clone $query)->whereRaw('LOWER(productName) = ?', [strtolower($data['productName'])])->first();
            if ($existing) {
                $duplicates['name'] = $existing;
            }
        }

        return $duplicates;
    }

    /**
     * Generate the next internal code for the business.
     */
    public static function generateInternalCode(int $businessId): string
    {
        $lastProduct = static::where('business_id', $businessId)
            ->whereNotNull('internal_code')
            ->orderByRaw('CAST(internal_code AS UNSIGNED) DESC')
            ->first();

        if ($lastProduct && is_numeric($lastProduct->internal_code)) {
            return str_pad((int) $lastProduct->internal_code + 1, 6, '0', STR_PAD_LEFT);
        }

        return '000001';
    }
}
