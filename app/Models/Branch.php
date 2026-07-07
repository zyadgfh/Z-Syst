<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'company_id',
        'address',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (self $branch): void {
            static::clearCompanyBranchCountCache($branch->company_id);
        });

        static::updated(function (self $branch): void {
            if ($branch->wasChanged('company_id')) {
                static::clearCompanyBranchCountCache((int) $branch->getOriginal('company_id'));
            }

            static::clearCompanyBranchCountCache($branch->company_id);
        });

        static::deleted(function (self $branch): void {
            static::clearCompanyBranchCountCache($branch->company_id);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_branch_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_branch_id');
    }

    protected static function clearCompanyBranchCountCache(int $companyId): void
    {
        Cache::forget("company:{$companyId}:branches");
        Cache::forget("company:{$companyId}:limit");
        Cache::forget('branch_limits_stats');
    }

    public static function getTotalBranchesCount(): int
    {
        return static::count();
    }

    public static function getBranchesCountByCompany(int $companyId): int
    {
        return static::where('company_id', $companyId)->count();
    }
}
