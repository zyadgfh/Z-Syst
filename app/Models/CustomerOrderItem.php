<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_order_id',
        'product_id',
        'product_name',
        'product_sku',
        'unit_price',
        'quantity',
        'total_price',
        'meta',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
        'meta' => 'json',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
