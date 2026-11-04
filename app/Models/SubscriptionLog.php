<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SubscriptionLog - table `subscription_logs` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SubscriptionLog extends Model
{
    protected $table = 'subscription_logs';

    protected $fillable = [
        'subscription_id',
        'business_id',
        'action',
        'old_data',
        'new_data',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'subscription_id' => 'integer',
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
