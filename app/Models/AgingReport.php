<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AgingReport - table `aging_reports` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class AgingReport extends Model
{
    protected $table = 'aging_reports';

    protected $fillable = [
        'business_id',
        'supplier_id',
        'report_date',
        'period_30',
        'period_60',
        'period_90',
        'period_90_plus',
        'total',
        'generated_at',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'report_date' => 'date',
        'period_30' => 'decimal:2',
        'period_60' => 'decimal:2',
        'period_90' => 'decimal:2',
        'period_90_plus' => 'decimal:2',
        'total' => 'decimal:2',
        'generated_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
