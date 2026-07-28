<?php

namespace App\Shared\Middleware;

use App\Exceptions\BranchLimitExceededException;
use App\Services\BranchLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceBranchLimit
{
    public function __construct(private BranchLimitService $branchLimitService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route('company')) {
            $company = $request->route('company');

            if (! $this->branchLimitService->canCreateBranch($company)) {
                throw new BranchLimitExceededException(
                    'You have reached the maximum number of branches allowed by your subscription.',
                    403,
                    [
                        'max_branches' => $this->branchLimitService->getEffectiveLimit($company),
                        'current_branches' => $this->branchLimitService->getCurrentBranchCount($company),
                        'remaining' => $this->branchLimitService->getRemainingBranches($company),
                    ]
                );
            }
        }

        return $next($request);
    }
}
