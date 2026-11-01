<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LoyaltyProgram - table `loyalty_programs` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class LoyaltyProgram extends Model
{
    protected $table = 'loyalty_programs';

    protected $fillable = [
        'business_id',
        'name',
        'points_per_currency',
        'min_points_for_reward',
        'is_active',
    ];

    protected $casts = [
        'points_per_currency' => 'integer',
        'min_points_for_reward' => 'integer',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
