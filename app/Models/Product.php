<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

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

    public function expiring_item()
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

    public function manufacterer(): BelongsTo
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

    protected $casts = [
        'meta' => 'json',
        'images' => 'json',
    ];
}
