<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Income - table `incomes` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Income extends Model
{
    protected $table = 'incomes';

    protected $fillable = [
        'business_id',
        'amount',
        'income_category_id',
        'income_for',
        'payment_type',
        'reference_no',
        'note',
        'income_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'income_category_id' => 'integer',
        'income_date' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function incomeCategory(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }
}
