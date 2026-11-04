<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UsageRecord - table `usage_records` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class UsageRecord extends Model
{
    protected $table = 'usage_records';

    protected $fillable = [
        'business_id',
        'subscription_id',
        'metric_name',
        'quantity',
        'recorded_at',
    ];

    protected $casts = [
        'subscription_id' => 'integer',
        'quantity' => 'integer',
        'recorded_at' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }
}
