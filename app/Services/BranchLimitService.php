<?php

namespace App\Services;

use App\Exceptions\BranchLimitExceededException;
use App\Models\Branch;
use App\Models\Company;
use App\Notifications\BranchLimitUpdatedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BranchLimitService
{
    public function __construct(private int $warningThreshold = 80, private int $criticalThreshold = 90) {}

    public function canCreateBranch(Company $company): bool
    {
        $limit = $this->getEffectiveLimit($company);

        if ($limit === null) {
            return true;
        }

        $currentCount = $this->getCurrentBranchCount($company);

        return $currentCount < $limit;
    }

    public function getEffectiveLimit(Company $company): ?int
    {
        return $company->effective_max_branches;
    }

    public function getCurrentBranchCount(Company $company): int
    {
        return Cache::remember("company:{$company->id}:branches", config('branch-limit.cache_ttl', 300), function () use ($company) {
            return $company->branches()->count();
        });
    }

    public function getRemainingBranches(Company $company): ?int
    {
        $limit = $this->getEffectiveLimit($company);

        if ($limit === null) {
            return null;
        }

        $currentCount = $this->getCurrentBranchCount($company);

        return max(0, $limit - $currentCount);
    }

    public function getUsagePercentage(Company $company): float
    {
        $limit = $this->getEffectiveLimit($company);

        if ($limit === null || $limit === 0) {
            return 0.0;
        }

        $currentCount = $this->getCurrentBranchCount($company);

        return round(($currentCount / $limit) * 100, 2);
    }

    public function checkAndNotify(Company $company): void
    {
        $percentage = $this->getUsagePercentage($company);

        if ($percentage >= $this->criticalThreshold) {
            $company->notify(new BranchLimitUpdatedNotification($company, 'critical'));
        } elseif ($percentage >= $this->warningThreshold) {
            $company->notify(new BranchLimitUpdatedNotification($company, 'warning'));
        }
    }

    public function enforceBeforeCreate(Company $company): void
    {
        if (! $this->canCreateBranch($company)) {
            $limit = $this->getEffectiveLimit($company);
            $current = $this->getCurrentBranchCount($company);

            throw new BranchLimitExceededException(
                'You have reached the maximum number of branches allowed by your subscription.',
                403,
                [
                    'max_branches' => $limit,
                    'current_branches' => $current,
                    'remaining' => $this->getRemainingBranches($company),
                ]
            );
        }
    }

    public function incrementBranchCount(Company $company): void
    {
        $count = $this->getCurrentBranchCount($company);

        Cache::put("company:{$company->id}:branches", $count + 1, config('branch-limit.cache_ttl', 300));

        $this->checkAndNotify($company);
    }

    public function decrementBranchCount(Company $company): void
    {
        $count = $this->getCurrentBranchCount($company);

        if ($count > 0) {
            Cache::put("company:{$company->id}:branches", $count - 1, config('branch-limit.cache_ttl', 300));
        }
    }

    public function updateBranchLimit(Company $company, ?int $maxBranches, bool $isUnlimited): void
    {
        DB::transaction(function () use ($company, $maxBranches, $isUnlimited) {
            $company->update([
                'max_branches' => $maxBranches,
                'is_unlimited_branches' => $isUnlimited,
                'default_branch_limit' => $maxBranches,
                'branch_limit_updated_at' => now(),
                'branch_limit_updated_by' => Auth::id(),
            ]);

            $this->clearCache($company);
        });
    }

    public function resetToDefault(Company $company): void
    {
        DB::transaction(function () use ($company) {
            $company->update([
                'max_branches' => $company->default_branch_limit,
                'is_unlimited_branches' => false,
                'branch_limit_updated_at' => now(),
                'branch_limit_updated_by' => Auth::id(),
            ]);

            $this->clearCache($company);
        });
    }

    public function clearCache(Company $company): void
    {
        Cache::forget("company:{$company->id}:branches");
        Cache::forget("company:{$company->id}:limit");
        Cache::forget('branch_limits_stats');
    }

    public function getAllBranchStats(): array
    {
        return Cache::remember('branch_limits_stats', config('branch-limit.stats_cache_ttl', 600), function () {
            return [
                'total_companies' => Company::count(),
                'unlimited_companies' => Company::where('is_unlimited_branches', true)->count(),
                'companies_near_limit' => Company::where('is_unlimited_branches', false)
                    ->whereNotNull('max_branches')
                    ->get()
                    ->filter(function ($company) {
                        return $company->branch_usage_percentage >= 80;
                    })
                    ->count(),
                'companies_at_limit' => Company::where('is_unlimited_branches', false)
                    ->whereNotNull('max_branches')
                    ->get()
                    ->filter(function ($company) {
                        return $company->is_at_limit;
                    })
                    ->count(),
                'total_branches' => Branch::count(),
            ];
        });
    }
}
