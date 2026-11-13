<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard block for the setup/update wizard shipped by `safiull/laravel-installer`.
 *
 * Why this exists
 * ---------------
 * That package registers its routes unconditionally from its own service
 * provider (Laravel\LaravelInstaller\Providers\LaravelInstallerServiceProvider
 * calls loadRoutesFrom() in boot()). Its only guard is the `canInstall`
 * middleware, which reduces to:
 *
 *     file_exists(storage_path('installed'))
 *
 * `storage/installed` is git-ignored, so a fresh container — or any deploy
 * where the volume is not warm — has no such file, and the wizard is wide
 * open to the internet:
 *
 *   GET  /install/environment/classic   → prints the current environment
 *   POST /install/environment/saveClassic → REWRITES .env (APP_KEY, DB_*, APP_DEBUG)
 *   GET  /install/database              → runs migrations against the live DB
 *   GET  /install/final                 → migrate --force + module seeds
 *   GET  /update/final                  → deliberately OUTSIDE the guard, per the
 *                                         package's own comment in Routes/web.php
 *
 * Rewriting APP_KEY invalidates every session; rewriting DB_* points the
 * application at an attacker's database; APP_DEBUG=true then leaks the whole
 * configuration through any 500. That is a full compromise, so the routes are
 * refused at the edge of the framework, in every environment, before routing
 * is ever consulted.
 *
 * nginx (docker/nginx.conf.template) returns 404 for the same prefixes; this
 * middleware is the layer that still holds when the app is served by
 * `artisan serve`, a different web server, or a platform router.
 *
 * @see RUNBOOK.md — "Setup wizard routes are blocked"
 */
class BlockInstallerRoutes
{
    /**
     * URL prefixes owned by the installer/updater package.
     *
     * Matched on the decoded path so an encoded separator (%2F) or a trailing
     * slash cannot be used to slip past, and anchored so an application route
     * that merely starts with the same letters is unaffected.
     */
    private const BLOCKED_PREFIXES = ['install', 'update', 'envato'];

    public function handle(Request $request, Closure $next): Response
    {
        $path = trim($request->path(), '/');
        $first = strtolower(explode('/', $path)[0]);

        if (in_array($first, self::BLOCKED_PREFIXES, true)) {
            // 404, not 403: the response must not confirm that a wizard exists
            // here at all. Nothing is logged — probes for these paths are
            // constant background noise and would only fill audit_logs.
            abort(404);
        }

        return $next($request);
    }
}
