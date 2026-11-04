<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BudgetAlert - table `budget_alerts` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class BudgetAlert extends Model
{
    protected $table = 'budget_alerts';

    protected $fillable = [
        'budget_id',
        'business_id',
        'alert_type',
        'threshold',
        'alert_sent_at',
        'resolved_at',
        'notes',
    ];

    protected $casts = [
        'budget_id' => 'integer',
        'threshold' => 'decimal:2',
        'alert_sent_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
