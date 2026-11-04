<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseOrderItem - table `purchase_order_items` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'business_id',
        'purchase_order_id',
        'product_id',
        'quantity',
        'received_quantity',
        'pending_quantity',
        'unit_price',
        'discount',
        'tax',
        'total',
        'notes',
    ];

    protected $casts = [
        'purchase_order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'pending_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
