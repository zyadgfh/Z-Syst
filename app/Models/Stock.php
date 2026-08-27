<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'product_id',
        'productStock',
        'batch_no',
        'expire_date',
        'barcode',
        'purchase_price',
        'cost_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expire_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get barcodes for the stock batch.
     */
    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class, 'batch_id');
    }

    /**
     * Get active barcodes for the stock batch.
     */
    public function activeBarcodes(): HasMany
    {
        return $this->hasMany(Barcode::class, 'batch_id')->where('is_active', true);
    }

    /**
     * Get stock movements for this batch.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'stock_id');
    }
}
