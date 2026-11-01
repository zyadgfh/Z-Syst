<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FefoLog - table `fefo_logs` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class FefoLog extends Model
{
    protected $table = 'fefo_logs';

    protected $fillable = [
        'business_id',
        'product_id',
        'stock_id',
        'sale_id',
        'sale_detail_id',
        'batch_no',
        'expire_date',
        'quantity_deducted',
        'quantity_remaining_after',
        'action_type',
        'notes',
        'meta',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'stock_id' => 'integer',
        'sale_id' => 'integer',
        'sale_detail_id' => 'integer',
        'expire_date' => 'date',
        'quantity_deducted' => 'integer',
        'quantity_remaining_after' => 'integer',
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

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function saleDetail(): BelongsTo
    {
        return $this->belongsTo(SaleDetail::class, 'sale_detail_id');
    }
}
