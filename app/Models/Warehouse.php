<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warehouse extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\WarehouseFactory::new();
    }

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'location',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Get total products count in this warehouse
     */
    public function getTotalProductsAttribute(): int
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * Get stock for a specific product
     */
    public function getStockForProduct(int $productId): ?WarehouseStock
    {
        return $this->stocks()->where('product_id', $productId)->first();
    }

    /**
     * Check if warehouse has sufficient stock for a product
     */
    public function hasSufficientStock(int $productId, int $quantity): bool
    {
        $stock = $this->getStockForProduct($productId);
        return $stock && $stock->quantity >= $quantity;
    }
}
