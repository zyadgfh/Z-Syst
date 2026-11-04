<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FinancialTransaction - table `financial_transactions` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class FinancialTransaction extends Model
{
    protected $table = 'financial_transactions';

    const UPDATED_AT = null;

    protected $fillable = [
        'business_id',
        'transaction_type',
        'direction',
        'payment_method',
        'amount',
        'reference_type',
        'reference_id',
        'register_id',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reference_id' => 'integer',
        'register_id' => 'integer',
        'metadata' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
