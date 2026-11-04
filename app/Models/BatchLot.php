<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BatchLot - table `batch_lots` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class BatchLot extends Model
{
    protected $table = 'batch_lots';

    protected $fillable = [
        'business_id',
        'product_id',
        'batch_number',
        'lot_number',
        'manufacture_date',
        'expiry_date',
        'recall_date',
        'supplier_name',
        'notes',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'recall_date' => 'date',
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
