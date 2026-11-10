<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QualityCheck - table `quality_checks` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class QualityCheck extends Model
{
    use TenantScoped;

    protected $table = 'quality_checks';

    /**
     * `GrnItem`-derived tenant key: this table's business_id is NOT NULL
     * in the live Supabase schema, but bulk inserts and queue jobs run without an
     * authenticated user (TenantScoped only fills from auth()). Derive it from the
     * parent row so the constraint can never be hit.
     * NOTE: hand-added — tools/generate-models.py must preserve this method.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->business_id) && $model->grn_item_id) {
                $model->business_id = GrnItem::where('id', $model->grn_item_id)->value('business_id');
            }
        });
    }

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
