<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validation rules that pin a reference to the caller's own tenant.
 *
 * Laravel's `exists:` rule runs on the query builder, so Eloquent global
 * scopes (TenantScoped) do NOT apply to it — a plain `exists:products,id`
 * accepts any tenant's row. These helpers add the missing business_id
 * condition, keyed off the VERIFIED principal only:
 *
 *   - authenticated non-superadmin → row must belong to their business;
 *   - superadmin / no request principal → plain existence check
 *     (console commands and the superadmin's reviewed cross-tenant access).
 *
 * Pair with a scoped lookup (Model::findOrFail) when the row is actually
 * loaded — validation alone must not be the only tenant boundary.
 */
final class TenantRules
{
    /**
     * `exists` rule restricted to the authenticated user's tenant.
     *
     * @return \Illuminate\Validation\Rule\Exists|string
     */
    public static function exists(string $table, string $column = 'id')
    {
        $user = Auth::user();

        if ($user
            && ($user->role ?? null) !== 'superadmin'
            && isset($user->business_id)
            && $user->business_id !== null) {
            return Rule::exists($table, $column)
                ->where('business_id', $user->business_id);
        }

        return "exists:{$table},{$column}";
    }
}
