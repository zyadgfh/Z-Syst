<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPriceHistory extends Model
{
    use HasFactory;

    protected $table = 'item_price_history';

    protected $fillable = [
        'product_id', 'business_id', 'user_id',
        'purchase_without_tax', 'purchase_with_tax',
        'sales_price', 'wholesale_price', 'minimum_selling_price',
        'change_reason', 'metadata',
    ];

    protected $casts = [
        'purchase_without_tax' => 'decimal:2',
        'purchase_with_tax' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'minimum_selling_price' => 'decimal:2',
        'metadata' => 'json',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
