<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Company extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'max_branches',
        'is_unlimited_branches',
        'default_branch_limit',
        'branch_limit_updated_at',
        'branch_limit_updated_by',
        'slug',
    ];

    protected $casts = [
        'is_unlimited_branches' => 'boolean',
        'max_branches' => 'integer',
        'default_branch_limit' => 'integer',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'slug' && ! Schema::hasColumn('companies', 'slug')) {
            // silently ignore slug when the column does not exist
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class);
    }

    public function branchLimitUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_limit_updated_by');
    }

    /**
     * Get the latest subscription for the company.
     */
    public function subscriptionLatest(): HasOne
    {
        return $this->hasOne(PlanSubscribe::class, 'company_id')->latestOfMany();
    }

    public function getCurrentBranchesCountAttribute(): int
    {
        return Cache::remember("company:{$this->id}:branches", config('branch-limit.cache_ttl', 300), function () {
            return $this->branches()->count();
        });
    }

    public function getRemainingBranchesAttribute(): ?int
    {
        if ($this->is_unlimited_branches || is_null($this->max_branches)) {
            return null;
        }

        return max(0, $this->max_branches - $this->current_branches_count);
    }

    public function getBranchUsagePercentageAttribute(): float
    {
        if ($this->is_unlimited_branches || is_null($this->max_branches) || $this->max_branches === 0) {
            return 0;
        }

        return round(($this->current_branches_count / $this->max_branches) * 100, 2);
    }

    public function getIsNearLimitAttribute(): bool
    {
        if ($this->is_unlimited_branches || is_null($this->max_branches)) {
            return false;
        }

        return $this->branch_usage_percentage >= 80;
    }

    public function getIsAtLimitAttribute(): bool
    {
        if ($this->is_unlimited_branches || is_null($this->max_branches)) {
            return false;
        }

        return $this->current_branches_count >= $this->max_branches;
    }

    public function canCreateBranch(): bool
    {
        if ($this->is_unlimited_branches || is_null($this->max_branches)) {
            return true;
        }

        return $this->current_branches_count < $this->max_branches;
    }

    public function getEffectiveMaxBranchesAttribute(): ?int
    {
        if ($this->is_unlimited_branches) {
            return null;
        }

        return $this->max_branches;
    }
}
