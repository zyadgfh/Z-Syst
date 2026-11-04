<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseReturnItem - table `purchase_return_items` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PurchaseReturnItem extends Model
{
    protected $table = 'purchase_return_items';

    const UPDATED_AT = null;

    protected $fillable = [
        'business_id',
        'purchase_return_id',
        'purchase_item_id',
        'product_id',
        'stock_id',
        'quantity',
        'refund_amount',
        'batch_no',
        'expire_date',
    ];

    protected $casts = [
        'purchase_return_id' => 'integer',
        'purchase_item_id' => 'integer',
        'product_id' => 'integer',
        'stock_id' => 'integer',
        'quantity' => 'integer',
        'refund_amount' => 'decimal:2',
        'expire_date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}
