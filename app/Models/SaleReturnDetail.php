<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SaleReturnDetail - table `sale_return_details` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SaleReturnDetail extends Model
{
    protected $table = 'sale_return_details';

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'sale_return_id',
        'sale_detail_id',
        'return_amount',
        'return_qty',
    ];

    protected $casts = [
        'sale_return_id' => 'integer',
        'sale_detail_id' => 'integer',
        'return_amount' => 'decimal:2',
        'return_qty' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class, 'sale_return_id');
    }

    public function saleDetail(): BelongsTo
    {
        return $this->belongsTo(SaleDetail::class, 'sale_detail_id');
    }
}
