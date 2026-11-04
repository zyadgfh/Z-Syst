<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerInteraction - table `customer_interactions` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class CustomerInteraction extends Model
{
    protected $table = 'customer_interactions';

    protected $fillable = [
        'business_id',
        'party_id',
        'type',
        'notes',
    ];

    protected $casts = [
        'party_id' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }
}
