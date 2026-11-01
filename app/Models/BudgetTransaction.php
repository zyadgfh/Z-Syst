<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BudgetTransaction - table `budget_transactions` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class BudgetTransaction extends Model
{
    protected $table = 'budget_transactions';

    protected $fillable = [
        'budget_id',
        'purchase_id',
        'amount',
        'transaction_date',
        'reference',
        'notes',
    ];

    protected $casts = [
        'budget_id' => 'integer',
        'purchase_id' => 'integer',
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }
}
