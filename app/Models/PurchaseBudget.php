<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseBudget - table `purchase_budgets` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PurchaseBudget extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_budgets';

    protected $fillable = [
        'business_id',
        'branch_id',
        'category_id',
        'period',
        'budget_amount',
        'spent_amount',
        'remaining_amount',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'budget_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
