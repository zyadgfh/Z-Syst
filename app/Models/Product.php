<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'productName',
        'business_id',
        'category_id',
        'unit_id',
        'type_id',
        'manufacturer_id',
        'box_size_id',
        'purchase_without_tax',
        'purchase_with_tax',
        'profit_percent',
        'sales_price',
        'alert_qty',
        'wholesale_price',
        'productCode',
        'images',
        'meta',
        'tax_id',
        'tax_type',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class)->where('productStock', '>', 0);
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

    public function expiringItem()
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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

    protected $casts = [
        'meta' => 'json',
        'images' => 'json',
    ];
}
