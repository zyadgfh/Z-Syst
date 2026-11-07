<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BusinessSettings — per-tenant key/value store (JSON values).
 * Used by the device & print preferences module and reusable beyond it.
 */
class BusinessSetting extends Model
{
    protected $fillable = ['business_id', 'key', 'value'];

    protected $casts = ['value' => 'array'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    /** Settings rows for a business belonging to a group key ("print", "scanner"…). */
    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('key', 'like', $group.'.%');
    }

    public static function valuesFor(int $businessId, string $prefix): HasMany
    {
        return static::forBusiness($businessId)->where('key', 'like', $prefix.'%');
    }
}
