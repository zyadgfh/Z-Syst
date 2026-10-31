<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QualityCheck - table `quality_checks` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class QualityCheck extends Model
{
    protected $table = 'quality_checks';

    protected $fillable = [
        'business_id',
        'grn_item_id',
        'check_date',
        'quality_status',
        'defects',
        'damage_quantity',
        'temperature',
        'humidity',
        'photos',
        'notes',
    ];

    protected $casts = [
        'grn_item_id' => 'integer',
        'check_date' => 'date',
        'damage_quantity' => 'integer',
        'temperature' => 'decimal:4',
        'humidity' => 'decimal:4',
        'photos' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function grnItem(): BelongsTo
    {
        return $this->belongsTo(GrnItem::class, 'grn_item_id');
    }
}
