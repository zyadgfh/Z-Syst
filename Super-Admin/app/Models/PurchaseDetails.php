<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDetails extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_id',
        'product_id',
        'productDealerPrice',
        'productPurchasePrice',
        'profit_percent',
        'productSalePrice',
        'productWholeSalePrice',
        'quantities',
        'stock_id',
        'mfg_date',
        'expire_date',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    protected $casts = [
        'stock_id' => 'integer',
        'purchase_id' => 'integer',
        'product_id' => 'integer',
        'productDealerPrice' => 'double',
        'productPurchasePrice' => 'double',
        'productSalePrice' => 'double',
        'productWholeSalePrice' => 'double',
        'quantities' => 'double',
        'profit_percent' => 'double',
    ];
}
