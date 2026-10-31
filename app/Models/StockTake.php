<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockTake - table `stock_takes` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class StockTake extends Model
{
    protected $table = 'stock_takes';

    protected $fillable = [
        'business_id',
        'branch_id',
        'audit_number',
        'audit_type',
        'status',
        'audit_date',
        'completed_at',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
