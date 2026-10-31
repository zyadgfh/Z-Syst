<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockAudit - table `stock_audits` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class StockAudit extends Model
{
    protected $table = 'stock_audits';

    protected $fillable = [
        'business_id',
        'audit_number',
        'audit_type',
        'status',
        'audit_date',
        'completed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'audit_date' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
