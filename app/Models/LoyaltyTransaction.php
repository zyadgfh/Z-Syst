<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LoyaltyTransaction - table `loyalty_transactions` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class LoyaltyTransaction extends Model
{
    protected $table = 'loyalty_transactions';

    protected $fillable = [
        'business_id',
        'loyalty_program_id',
        'party_id',
        'points',
        'type',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'loyalty_program_id' => 'integer',
        'party_id' => 'integer',
        'points' => 'integer',
        'reference_id' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }
}
