<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleDetails extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'price',
        'discount',
        'lossProfit',
        'quantities',
        'stock_id',
        'expire_date',
        'mfg_date',
        'productPurchasePrice',
        'warranty_guarantee_info',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    protected $casts = [
        'sale_id' => 'integer',
        'stock_id' => 'integer',
        'product_id' => 'integer',
        'price' => 'double',
        'discount' => 'double',
        'lossProfit' => 'double',
        'quantities' => 'double',
        'productPurchasePrice' => 'double',
        'warranty_guarantee_info' => 'json',
    ];
}
