<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IncomeCategory - table `income_categories` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class IncomeCategory extends Model
{
    protected $table = 'income_categories';

    protected $fillable = [
        'business_id',
        'categoryName',
        'categoryDescription',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
