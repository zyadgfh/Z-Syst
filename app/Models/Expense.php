<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Expense - table `expenses` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Expense extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        'business_id',
        'amount',
        'expense_category_id',
        'expanse_for',
        'payment_type',
        'reference_no',
        'note',
        'expense_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_category_id' => 'integer',
        'expense_date' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
