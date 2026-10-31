<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AutoOrderSuggestion - table `auto_order_suggestions` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class AutoOrderSuggestion extends Model
{
    protected $table = 'auto_order_suggestions';

    protected $fillable = [
        'business_id',
        'product_id',
        'preferred_supplier_id',
        'predicted_demand',
        'current_stock',
        'pending_purchases',
        'suggested_order_qty',
        'confidence_score',
        'priority',
        'status',
        'approved_by',
        'approved_at',
        'converted_purchase_id',
        'converted_at',
        'notes',
        'reasoning',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'preferred_supplier_id' => 'integer',
        'predicted_demand' => 'decimal:2',
        'current_stock' => 'decimal:4',
        'pending_purchases' => 'decimal:4',
        'suggested_order_qty' => 'decimal:4',
        'confidence_score' => 'decimal:2',
        'approved_at' => 'datetime',
        'converted_purchase_id' => 'integer',
        'converted_at' => 'datetime',
        'reasoning' => 'array',
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
