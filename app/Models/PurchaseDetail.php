<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseDetail - table `purchase_details` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PurchaseDetail extends Model
{
    protected $table = 'purchase_details';

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'purchase_id',
        'product_id',
        'purchase_without_tax',
        'purchase_with_tax',
        'profit_percent',
        'sales_price',
        'wholesale_price',
        'quantities',
        'batch_no',
        'expire_date',
    ];

    protected $casts = [
        'purchase_id' => 'integer',
        'product_id' => 'integer',
        'purchase_without_tax' => 'decimal:2',
        'purchase_with_tax' => 'decimal:2',
        'profit_percent' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'quantities' => 'integer',
        'expire_date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
