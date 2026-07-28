<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductInventoryAnalysis extends Model
{
    use HasFactory;

    protected $table = 'product_inventory_analysis';

    protected $fillable = [
        'business_id',
        'product_id',
        'report_id',
        'total_quantity_sold',
        'total_sales_value',
        'total_cogs',
        'average_stock_level',
        'turnover_ratio',
        'days_inventory_outstanding',
        'abc_category',
        'movement_category',
        'current_stock_value',
        'current_stock_qty',
        'stock_velocity',
        'meta',
    ];

    protected $casts = [
        'total_quantity_sold' => 'decimal:2',
        'total_sales_value' => 'decimal:2',
        'total_cogs' => 'decimal:2',
        'average_stock_level' => 'decimal:2',
        'turnover_ratio' => 'decimal:2',
        'days_inventory_outstanding' => 'decimal:2',
        'current_stock_value' => 'decimal:2',
        'current_stock_qty' => 'decimal:2',
        'stock_velocity' => 'decimal:2',
        'meta' => 'json',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(InventoryTurnoverReport::class, 'report_id');
    }

    /**
     * Get the label for movement category in Arabic.
     */
    public function getMovementLabelAttribute(): string
    {
        return match ($this->movement_category) {
            'fast' => 'سريع الحركة',
            'medium' => 'متوسط الحركة',
            'slow' => 'بطيء الحركة',
            'dead' => 'راكد',
            default => 'غير محدد',
        };
    }

    /**
     * Scope: Slow moving products.
     */
    public function scopeSlowMoving($query)
    {
        return $query->where('movement_category', 'slow');
    }

    /**
     * Scope: Dead stock products.
     */
    public function scopeDeadStock($query)
    {
        return $query->where('movement_category', 'dead');
    }

    /**
     * Scope: Fast moving products.
     */
    public function scopeFastMoving($query)
    {
        return $query->where('movement_category', 'fast');
    }

    /**
     * Scope: Category A products (highest value).
     */
    public function scopeCategoryA($query)
    {
        return $query->where('abc_category', 'A');
    }

    /**
     * Scope: Category B products.
     */
    public function scopeCategoryB($query)
    {
        return $query->where('abc_category', 'B');
    }

    /**
     * Scope: Category C products.
     */
    public function scopeCategoryC($query)
    {
        return $query->where('abc_category', 'C');
    }
}

