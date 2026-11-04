<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SaleReturn - table `sale_returns` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SaleReturn extends Model
{
    protected $table = 'sale_returns';

    protected $fillable = [
        'business_id',
        'sale_id',
        'invoice_no',
        'return_date',
    ];

    protected $casts = [
        'sale_id' => 'integer',
        'return_date' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
