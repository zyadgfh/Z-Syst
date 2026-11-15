<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coarse gate for the /admin back office.
 *
 * Three tiers of role, and it matters which tier a role is in:
 *
 *   shop-owner / superadmin / admin  → the whole panel. These roles own or
 *       administer a tenant, and the per-route `permission:` gates still apply
 *       on top of this.
 *
 *   staff → the operational areas only, listed in STAFF_OPERATIONAL below.
 *
 *   anything else (customer, …)    → denied.
 *
 * Why staff is a special case and not simply "allowed" or simply "denied"
 * ----------------------------------------------------------------------
 * `/admin` in this codebase is not an administrators-only panel: it is the whole
 * authenticated back office, including the till. `config/supabase.php` gives
 * `staff` 22 abilities — among them `sales-create` — and gives `shop-owner` 14
 * abilities that pointedly do **not** include `sales-create`. In other words the
 * pharmacy employee is the only role that may ring up a sale, and the POS routes
 * (`admin/pos/*`, `admin/dose/*`) carry no `permission:` gate of their own. So
 * this middleware is the only thing standing between a staff account and the
 * till: allowing staff wholesale lets an employee reach `users/delete-all` and
 * `system-settings`, and denying staff wholesale switches off the till for the
 * one role that is allowed to use it. Both were tried; both were wrong.
 *
 * The rule below is deny-by-default for staff, with one deliberate delegation:
 * a route that already carries its own authorization gate is left to that gate
 * rather than being re-decided here. That keeps `config/supabase.php` the single
 * source of truth — staff holds 8 of the 38 gated permissions
 * (deliveries-read/update, sales-create, shifts-read/create/update,
 * stocktake-read/create) and is refused the other 30 by the gate itself, so
 * `stocktake-update` and `products-create` stay out of reach without this file
 * having to duplicate the map and drift away from it.
 *
 * @see RUNBOOK.md §10.10 — authorization tiers on /admin
 * @see tests/Feature/SecurityHardeningTest — the tier boundaries are asserted
 */
class AdminMiddleware
{
    /**
     * Roles that own or administer a tenant: the whole panel.
     *
     * @var array<int, string>
     */
    private const OWNER_ROLES = ['shop-owner', 'superadmin', 'admin'];

    /**
     * Areas a `staff` user may reach on routes that carry no gate of their own.
     *
     * Each entry is the first URI segment after `/admin/`, and each one is here
     * because `config/supabase.php` grants staff a matching ability:
     *
     *   ''/get-dashboard/dashboard-reports  dashboard-read, reports-read
     *   notifications                       notifications-read
     *   barcodes                            barcodes-read
     *   device-setup                        printers-read (its printers/* and
     *                                       devices/* routes live under it)
     *   pos, dose, sales                    sales-create  ← the till
     *   shifts                              shifts-read/create/update
     *   stock-take                          stocktake-read/create
     *   deliveries                          deliveries-read/update
     *   warehouses, stock-transfers         warehouses-read, stock-transfers-read
     *   traceability                        traceability-read
     *   prescriptions                       prescriptions-read/create
     *   receipts                            receipts-read
     *   loyalty                             loyalty-read
     *   insurance                           insurance-claims-read/create (its
     *                                       claims/, policies/ and companies/
     *                                       routes live under it)
     *   profile, profiles                   self-service (own account only,
     *                                       including profile/two-factor)
     *
     * These are first URI segments, so nested areas are reached through their
     * parent: `device-setup/printers`, `insurance/claims` and
     * `profile/two-factor` are covered by `device-setup`, `insurance` and
     * `profile`. Listing the child instead would be a dead entry — the test
     * below fails on those.
     *
     * Adding an entry here widens what every staff account in every tenant can
     * do, so it needs a matching ability in the config map — not the other way
     * round. `SecurityHardeningTest` fails if an entry stops matching any route,
     * which is how a rename shows up instead of silently opening a hole.
     *
     * @var array<int, string>
     */
    private const STAFF_OPERATIONAL = [
        '',
        'get-dashboard',
        'dashboard-reports',
        'notifications',
        'barcodes',
        'device-setup',
        'pos',
        'dose',
        'sales',
        'shifts',
        'stock-take',
        'deliveries',
        'warehouses',
        'stock-transfers',
        'traceability',
        'prescriptions',
        'receipts',
        'loyalty',
        'insurance',
        'profile',
        'profiles',
    ];

    /**
     * Route middleware that already performs its own authorization. When one of
     * these is present this middleware steps aside and lets it decide.
     *
     * @var array<int, string>
     */
    private const OWN_GATES = ['permission:', 'role:', 'role_or_permission:'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user !== null) {
            $role = $user->role;

            if (in_array($role, self::OWNER_ROLES, true)) {
                return $next($request);
            }

            if ($role === 'staff' && $this->staffMayProceed($request)) {
                return $next($request);
            }
        }

        // A denial has to be visible to whatever is calling.
        //
        // For a browser, redirecting to / keeps the old behaviour and the user
        // ends up somewhere sensible. For an API/JSON caller a 302 is actively
        // wrong: HTTP clients follow it, land on the public homepage and read a
        // 200 HTML body as "the call worked", so an unauthorised request looks
        // successful and the Flutter client silently swallows it. Those get a
        // 403 with no body details — the caller is authenticated, just not
        // allowed, and saying more only helps whoever is mapping the panel.
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                // Plain text, not a translation key: this repository has no PHP
                // language files (lang/ holds country-list JSON only), so
                // __('errors.forbidden') would put the raw key in the response.
                'message' => 'This action is not permitted for your account.',
            ], 403);
        }

        return redirect('/');
    }

    /**
     * May a `staff` user proceed down this route?
     */
    private function staffMayProceed(Request $request): bool
    {
        // A route that authorizes on its own is left to that authorization, so
        // config/supabase.php stays the only place the map lives.
        if ($this->routeHasOwnGate($request)) {
            return true;
        }

        // Everything else is deny-by-default: only the operational areas pass.
        return in_array($this->areaAfterAdmin($request), self::STAFF_OPERATIONAL, true);
    }

    /**
     * Does the matched route carry its own authorization middleware?
     */
    private function routeHasOwnGate(Request $request): bool
    {
        $route = $request->route();

        if ($route === null) {
            return false;
        }

        foreach ((array) $route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            foreach (self::OWN_GATES as $gate) {
                if (str_starts_with($middleware, $gate)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * First URI segment after the `/admin` prefix ('' for the panel root).
     */
    private function areaAfterAdmin(Request $request): string
    {
        $path = trim($request->path(), '/');

        if ($path === 'admin') {
            return '';
        }

        if (str_starts_with($path, 'admin/')) {
            $path = substr($path, strlen('admin/'));
        }

        return explode('/', $path)[0];
    }

    /**
     * Exposed for tests: the operational allowlist, so a test can assert every
     * entry still matches a real route instead of going stale.
     *
     * @return array<int, string>
     */
    public static function staffOperationalAreas(): array
    {
        return self::STAFF_OPERATIONAL;
    }
}
