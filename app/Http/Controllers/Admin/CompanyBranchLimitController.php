<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkUpdateBranchLimitsRequest;
use App\Http\Requests\Admin\UpdateCompanyBranchLimitRequest;
use App\Models\Company;
use App\Notifications\BranchLimitUpdatedNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CompanyBranchLimitController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Company::class);

        $query = Company::query()
            ->select([
                'id', 'name', 'max_branches', 'is_unlimited_branches',
                'default_branch_limit', 'branch_limit_updated_at',
                'branch_limit_updated_by',
            ])
            ->withCount('branches')
            ->with(['branchLimitUpdatedBy:id,name']);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($companyQuery) use ($search): void {
                $companyQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        if ($request->filled('branch_limit_status')) {
            $status = $request->string('branch_limit_status')->toString();

            match ($status) {
                'unlimited' => $query->where('is_unlimited_branches', true),
                'limited' => $query->where('is_unlimited_branches', false)->whereNotNull('max_branches'),
                'near_limit' => $query->where('is_unlimited_branches', false)->whereNotNull('max_branches'),
                default => null,
            };
        }

        if ($request->filled('min_branches')) {
            $query->where('max_branches', '>=', (int) $request->integer('min_branches'));
        }

        if ($request->filled('max_branches')) {
            $query->where('max_branches', '<=', (int) $request->integer('max_branches'));
        }

        $companies = $query->paginate(25);

        return response()->json($companies);
    }

    public function update(UpdateCompanyBranchLimitRequest $request, Company $company)
    {
        $this->authorize('updateBranchLimit', $company);

        $validated = $request->validated();

        return DB::transaction(function () use ($company, $validated) {
            if (! empty($validated['reset_to_default'])) {
                $company->update([
                    'max_branches' => $company->default_branch_limit,
                    'is_unlimited_branches' => false,
                    'branch_limit_updated_at' => now(),
                    'branch_limit_updated_by' => Auth::id(),
                ]);
            } else {
                $company->update([
                    'max_branches' => $validated['max_branches'],
                    'is_unlimited_branches' => $validated['is_unlimited_branches'] ?? false,
                    'default_branch_limit' => $company->default_branch_limit ?? $validated['max_branches'],
                    'branch_limit_updated_at' => now(),
                    'branch_limit_updated_by' => Auth::id(),
                ]);
            }

            $this->clearCompanyCache($company);

            $company->refresh();

            $usagePercentage = $company->branch_usage_percentage;
            if ($usagePercentage >= 80) {
                $company->notify(new BranchLimitUpdatedNotification($company, 'limit_reached'));
            } else {
                $company->notify(new BranchLimitUpdatedNotification($company, 'updated'));
            }

            return response()->json([
                'message' => 'Branch limit updated successfully.',
                'data' => $company->loadCount('branches'),
            ]);
        });
    }

    public function bulkUpdate(BulkUpdateBranchLimitsRequest $request)
    {
        $validated = $request->validated();
        $results = [];

        DB::transaction(function () use ($validated, &$results) {
            foreach ($validated['updates'] as $update) {
                $company = Company::find($update['company_id']);

                if (! $company) {
                    $results[] = [
                        'company_id' => $update['company_id'],
                        'status' => 'error',
                        'message' => 'Company not found.',
                    ];

                    continue;
                }

                try {
                    $company->update([
                        'max_branches' => $update['max_branches'],
                        'is_unlimited_branches' => $update['is_unlimited_branches'] ?? false,
                        'default_branch_limit' => $company->default_branch_limit ?? $update['max_branches'],
                        'branch_limit_updated_at' => now(),
                        'branch_limit_updated_by' => Auth::id(),
                    ]);

                    $this->clearCompanyCache($company);

                    $results[] = [
                        'company_id' => $company->id,
                        'status' => 'success',
                        'message' => 'Branch limit updated.',
                        'data' => $company->loadCount('branches'),
                    ];

                    $usagePercentage = $company->branch_usage_percentage;
                    if ($usagePercentage >= 80) {
                        $company->notify(new BranchLimitUpdatedNotification($company, 'limit_reached'));
                    } else {
                        $company->notify(new BranchLimitUpdatedNotification($company, 'updated'));
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'company_id' => $company->id,
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'message' => 'Bulk update completed.',
            'results' => $results,
        ]);
    }

    public function show(Company $company)
    {
        $this->authorize('view', $company);

        $company->loadCount('branches');
        $company->load('branchLimitUpdatedBy:id,name');

        return response()->json($company);
    }

    public function checkAvailability(Company $company): JsonResponse
    {
        $this->authorize('checkAvailability', $company);

        $company->loadCount('branches');

        return response()->json([
            'company_id' => $company->id,
            'company_name' => $company->name,
            'max_branches' => $company->effective_max_branches,
            'is_unlimited' => $company->is_unlimited_branches,
            'current_branches' => $company->branches_count,
            'remaining' => $company->remaining_branches,
            'usage_percentage' => $company->branch_usage_percentage,
            'can_create' => $company->canCreateBranch(),
            'is_near_limit' => $company->is_near_limit,
            'is_at_limit' => $company->is_at_limit,
        ]);
    }

    private function clearCompanyCache(Company $company): void
    {
        Cache::forget("company:{$company->id}:branches");
        Cache::forget("company:{$company->id}:limit");
        Cache::forget('branch_limits_stats');
    }
}
