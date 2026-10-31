<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock - table `stocks` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Stock extends Model
{
    protected $table = 'stocks';

    protected $fillable = [
        'business_id',
        'product_id',
        'product_stock',
        'batch_no',
        'expire_date',
        'barcode',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'product_stock' => 'integer',
        'expire_date' => 'date',
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
