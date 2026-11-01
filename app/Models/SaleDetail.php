<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SaleDetail - table `sale_details` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SaleDetail extends Model
{
    protected $table = 'sale_details';

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'sale_id',
        'product_id',
        'price',
        'purchase_price',
        'loss_profit',
        'batch_no',
        'expire_date',
        'quantities',
    ];

    protected $casts = [
        'sale_id' => 'integer',
        'product_id' => 'integer',
        'price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'loss_profit' => 'decimal:2',
        'expire_date' => 'date',
        'quantities' => 'integer',
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
