<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QualityReport - table `quality_reports` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class QualityReport extends Model
{
    protected $table = 'quality_reports';

    protected $fillable = [
        'business_id',
        'branch_id',
        'supplier_id',
        'report_date',
        'total_checks',
        'passed',
        'failed',
        'pass_rate',
        'notes',
        'generated_by',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'report_date' => 'date',
        'total_checks' => 'integer',
        'passed' => 'integer',
        'failed' => 'integer',
        'pass_rate' => 'decimal:2',
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
