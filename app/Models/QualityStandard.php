<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QualityStandard - table `quality_standards` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class QualityStandard extends Model
{
    protected $table = 'quality_standards';

    protected $fillable = [
        'business_id',
        'category_id',
        'temperature_min',
        'temperature_max',
        'humidity_min',
        'humidity_max',
        'acceptable_defects',
        'criteria',
        'is_active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'temperature_min' => 'decimal:4',
        'temperature_max' => 'decimal:4',
        'humidity_min' => 'decimal:4',
        'humidity_max' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
