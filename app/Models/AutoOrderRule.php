<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AutoOrderRule - table `auto_order_rules` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class AutoOrderRule extends Model
{
    protected $table = 'auto_order_rules';

    protected $fillable = [
        'business_id',
        'product_id',
        'enabled',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'safety_stock',
        'lead_time_days',
        'min_order_qty',
        'max_order_qty',
        'order_multiple',
        'preferred_supplier_id',
        'auto_approve',
        'meta',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'enabled' => 'boolean',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'reorder_point' => 'decimal:4',
        'safety_stock' => 'decimal:4',
        'lead_time_days' => 'integer',
        'min_order_qty' => 'decimal:4',
        'max_order_qty' => 'decimal:4',
        'order_multiple' => 'decimal:4',
        'preferred_supplier_id' => 'integer',
        'auto_approve' => 'boolean',
        'meta' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
