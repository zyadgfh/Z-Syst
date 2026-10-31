<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CashRegisterTransaction - table `cash_register_transactions` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class CashRegisterTransaction extends Model
{
    protected $table = 'cash_register_transactions';

    const UPDATED_AT = null;

    protected $fillable = [
        'business_id',
        'register_id',
        'type',
        'amount',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'register_id' => 'integer',
        'amount' => 'decimal:2',
        'reference_id' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
