<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Model - Enhanced for Pharmacy Management
 * 
 * Represents medications/products in the pharmacy system
 */
class Product extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Basic Info
        'company_id',
        'branch_id',
        'category_id',
        'manufacturer_id',
        
        // Product Names
        'generic_name',
        'brand_name',
        'product_name', // Alias for productName
        
        // Identification
        'product_code', // SKU
        'barcode',
        'internal_code',
        
        // Pharmacy Specific
        'dosage_form',
        'strength',
        'unit_of_measure',
        'package_size',
        
        // Prescription & Control
        'prescription_required',
        'controlled_substance_schedule', // 1, 2, 3, 4, 5 or null
        'requires_special_handling',
        
        // Storage
        'storage_conditions',
        'shelf_life_months',
        
        // Stock Management
        'min_stock',
        'max_stock',
        'reorder_level',
        'alert_qty',
        
        // Pricing
        'purchase_price',
        'purchase_without_tax',
        'purchase_with_tax',
        'sales_price',
        'wholesale_price',
        'cost_price',
        'price_currency',
        'tax_id',
        'tax_rate',
        'profit_percent',
        'markup_percent',
        
        // Description
        'description',
        'usage_instructions',
        'side_effects',
        
        // Images & Media
        'images',
        'product_image',
        'meta',
        
        // Status
        'is_active',
        'is_taxable',
        'track_inventory',
        
        // Audit
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'prescription_required' => 'boolean',
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'track_inventory' => 'boolean',
        'requires_special_handling' => 'boolean',
        'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'alert_qty' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'purchase_without_tax' => 'decimal:2',
        'purchase_with_tax' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'profit_percent' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'shelf_life_months' => 'integer',
        'storage_conditions' => 'json',
        'images' => 'json',
        'meta' => 'json',
    ];

    /**
     * Get all inventory records for this product.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get stock movements for this product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get product variants (different package sizes).
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get product price history.
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    /**
     * Get drug interactions where this product is involved.
     */
    public function drugInteractionsA(): HasMany
    {
        return $this->hasMany(DrugInteraction::class, 'product_a_id');
    }

    /**
     * Get drug interactions where this product is involved.
     */
    public function drugInteractionsB(): HasMany
    {
        return $this->hasMany(DrugInteraction::class, 'product_b_id');
    }

    /**
     * Get all drug interactions for this product.
     */
    public function drugInteractions(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'drug_interactions', 'product_a_id', 'product_b_id')
            ->withPivot(['interaction_level', 'description', 'clinical_effects', 'management'])
            ->as('interaction');
    }

    /**
     * Get the category for this product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the manufacturer for this product.
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Get the tax for this product.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the creator of this product.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater of this product.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get sales for this product.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get purchase orders for this product.
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Calculate current stock (FEFO aware).
     */
    public function getCurrentStock(): float
    {
        return $this->inventory()->sum('quantity');
    }

    /**
     * Check if product is expired.
     */
    public function hasExpiredBatches(): bool
    {
        return $this->inventory()
            ->where('expiry_date', '<', now()->toDateString())
            ->where('quantity', '>', 0)
            ->exists();
    }

    /**
     * Check if product needs reorder.
     */
    public function needsReorder(): bool
    {
        return $this->getCurrentStock() <= $this->reorder_level;
    }

    /**
     * Check if this is a controlled substance.
     */
    public function isControlledSubstance(): bool
    {
        return !empty($this->controlled_substance_schedule);
    }

    /**
     * Scope: Search products.
     */
    public function scopeSearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('generic_name', 'ilike', "%{$search}%")
                ->orWhere('brand_name', 'ilike', "%{$search}%")
                ->orWhere('barcode', 'ilike', "%{$search}%")
                ->orWhere('product_code', 'ilike', "%{$search}%")
                ->orWhere('product_name', 'ilike', "%{$search}%");
        });
    }

    /**
     * Scope: Filter by barcode.
     */
    public function scopeByBarcode($query, string $barcode): void
    {
        $query->where('barcode', $barcode);
    }

    /**
     * Scope: Filter active products only.
     */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope: Filter by prescription requirement.
     */
    public function scopePrescriptionRequired($query, bool $required = true): void
    {
        $query->where('prescription_required', $required);
    }

    /**
     * Scope: Filter low stock.
     */
    public function scopeLowStock($query): void
    {
        $query->whereHas('inventory', function ($q) {
            $q->selectRaw('SUM(quantity) as total_qty')
                ->havingRaw('total_qty <= products.reorder_level');
        });
    }

    /**
     * Scope: Filter expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 90): void
    {
        $query->whereHas('inventory', function ($q) use ($days) {
            $q->whereBetween('expiry_date', [now(), now()->addDays($days)])
                ->where('quantity', '>', 0);
        });
    }
}