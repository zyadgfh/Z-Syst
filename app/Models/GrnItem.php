<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GrnItem - table `grn_items` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class GrnItem extends Model
{
    protected $table = 'grn_items';

    protected $fillable = [
        'business_id',
        'grn_id',
        'product_id',
        'ordered_quantity',
        'received_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'batch_number',
        'expiry_date',
        'purchase_price',
        'notes',
    ];

    protected $casts = [
        'grn_id' => 'integer',
        'product_id' => 'integer',
        'ordered_quantity' => 'integer',
        'received_quantity' => 'integer',
        'accepted_quantity' => 'integer',
        'rejected_quantity' => 'integer',
        'expiry_date' => 'date',
        'purchase_price' => 'decimal:2',
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
