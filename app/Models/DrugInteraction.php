<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DrugInteraction - table `drug_interactions` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class DrugInteraction extends Model
{
    /**
     * This table's business_id is NULLABLE in the live schema: NULL rows are
     * platform-global and must stay visible to every tenant.
     */
    protected static function tenantIncludesGlobals(): bool
    {
        return true;
    }

    
    use TenantScoped;
protected $table = 'drug_interactions';

    protected $fillable = [
        'business_id',
        'drug_a_name',
        'drug_b_name',
        'severity',
        'description',
        'mechanism',
        'recommendation',
        'source',
        'category',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
