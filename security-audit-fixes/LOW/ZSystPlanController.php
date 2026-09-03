<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZSystPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ZSystPlanController extends Controller
{
    /**
     * FIX (L-01): Cap per_page to max 100 to prevent resource exhaustion.
     * FIX (L-02): Sanitize search input to prevent wildcard abuse.
     * FIX (L-03): deleteAll() should only soft-delete, not force-delete.
     */

    public function zsystFilter(Request $request)
    {
        $query = ZSystPlan::query();

        if ($request->has('search') && !empty($request->search)) {
            // L-02: Sanitize search input — trim, studi, escapes for safe LIKE
            $search = Str::of($request->search)->trim()->studi()->escapes()->value();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // L-01: Cap per_page to prevent resource exhaustion
        $perPage = min((int) ($request->per_page ?? 10), 100);
        $perPage = max($perPage, 1); // minimum 1

        return response()->json(
            $query->paginate($perPage)
        );
    }

    public function deleteAll(Request $request)
    {
        // L-03: Only soft-delete (use delete, not forceDelete)
        // forceDelete should require explicit confirmation + audit log
        $count = ZSystPlan::query()->delete(); // soft-delete only

        \App\Helpers\StructuredLogger::audit('plans.soft_deleted_all', auth()->user(), [
            'count' => $count,
        ]);

        return response()->json([
            'message' => "{$count} plans soft-deleted successfully.",
        ]);
    }

    /**
     * If hard-delete is truly needed, gate it behind admin role + confirmation:
     */
    public function forceDeleteAll(Request $request)
    {
        // Require explicit confirmation token
        if ($request->confirm !== 'DELETE_ALL_PLANS') {
            return response()->json([
                'error' => 'Send confirm=DELETE_ALL_PLANS to proceed with permanent deletion.',
            ], 422);
        }

        // Require admin role
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Only administrators can permanently delete plans.');
        }

        $count = ZSystPlan::query()->forceDelete();

        \App\Helpers\StructuredLogger::security('plans.force_deleted_all', auth()->user(), [
            'count' => $count,
        ]);

        return response()->json([
            'message' => "{$count} plans permanently deleted.",
        ]);
    }
}
