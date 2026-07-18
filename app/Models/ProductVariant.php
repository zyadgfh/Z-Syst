<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Variant Model
 * 
 * Represents different package sizes/formulations of a product
 * E.g., 10 tablets, 20 tablets, 500mg, 1000mg, etc.
 */
class ProductVariant extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_variants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'product_id',
        'variant_name',
        'barcode',
        'package_size',
        'unit_quantity',
        'unit_of_measure',
        'purchase_price',
        'sales_price',
        'wholesale_price',
        'tax_rate',
        'sku',
        'is_default',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'unit_quantity' => 'decimal:2',
    ];

    /**
     * Get the product this variant belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the company that owns this variant.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the creator of this variant.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater of this variant.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get display name (product name + variant).
     */
    public function getDisplayNameAttribute(): string
    {
        $productName = $this->product->generic_name ?? $this->product->brand_name;
        return "{$productName} - {$this->variant_name}";
    }

    /**
     * Check if this variant is low stock.
     */
    public function isLowStock(): bool
    {
        return $this->inventory()->sum('quantity') <= ($this->reorder_level ?? 0);
    }

    /**
     * Get inventory for this variant.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'product_variant_id');
    }
}