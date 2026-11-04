<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SaleItem - table `sale_items` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SaleItem extends Model
{
    protected $table = 'sale_items';

    protected $fillable = [
        'business_id',
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'batch_no',
    ];

    protected $casts = [
        'sale_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
