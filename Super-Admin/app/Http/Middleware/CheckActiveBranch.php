<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveBranch
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!moduleCheck('MultiBranchAddon')) {
            return $next($request);
        }

        $user = auth()->user();

        // If no user found, just continue
        if (!$user) {
            return $next($request);
        }

        if (in_array($user->role, ['shop-owner', 'staff']) && $user->accessToMultiBranch()) {
            if ($request->routeIs([
                'business.sales.create',
                'business.sales.store',
                'business.products.show',
                'business.products.create',
                'business.products.store',
                'business.products.edit',
                'business.products.update',
                'hrm.employees.create',
                'hrm.employees.store',
                'business.sale-returns.create',
                'business.sale-returns.store',
                'business.purchase-returns.create',
                'business.purchase-returns.store',
                'business.purchases.create',
                'business.purchases.store',
                'business.expenses.store',
                'business.parties.create',
                'business.parties.store',
                'business.parties.edit',
                'business.parties.update',
            ])) {

                $totalBranch = branch_count();

                if ($totalBranch > 1) {
                    return $request->wantsJson()
                        ? response()->json(
                            [
                                'redirect' => route('multibranch.branches.index'),
                                'message' => 'Please select a branch to continue.'
                            ],
                            406
                        )
                        : redirect()->route('multibranch.branches.index')->with('warning', 'Please select a branch to continue.');
                } elseif ($totalBranch == 1) {
                    $branch = Branch::where('business_id', $user->business_id)->first();
                    if ($branch) {
                        $user->update([
                            'active_branch_id' => $branch->id
                        ]);
                    }

                    return $next($request);
                }
            }
        }

        return $next($request);
    }
}
