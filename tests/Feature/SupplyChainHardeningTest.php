<?php

namespace Tests\Feature;

use App\Services\SecurityService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guards for what the project *is made of* rather than how it behaves at
 * runtime: the dependency manifests, the lockfiles, and the files that a
 * removed package used to publish into the repository.
 *
 * No RefreshDatabase here on purpose. Every assertion reads a file or the route
 * table, so this suite stays fast and still fails if someone re-adds the setup
 * wizard, pins a vulnerable build tool, or starts tracking user uploads again.
 */
class SupplyChainHardeningTest extends TestCase
{
    /**
     * safiull/laravel-installer was removed outright (not merely disabled) on
     * 2026-11-13. Its service provider loaded routes unconditionally, extended
     * the session guard with its own 'sessionss' driver, and its FinalController
     * ran migrate --force plus .env rewrites. Disabling it via config still left
     * that code installed and one forgotten config key away from being live.
     */
    public function test_the_setup_wizard_package_is_not_a_dependency(): void
    {
        $composer = $this->composerManifest();

        $this->assertArrayNotHasKey('safiull/laravel-installer', $composer['require'] ?? []);
        $this->assertArrayNotHasKey('safiull/laravel-installer', $composer['require-dev'] ?? []);

        $lock = $this->composerLock();
        foreach (['packages', 'packages-dev'] as $section) {
            $names = array_column($lock[$section] ?? [], 'name');
            $this->assertNotContains('safiull/laravel-installer', $names, "$section must not lock the installer");
        }
    }

    /**
     * Removing the package is not enough: it had been published into the
     * repository, and published views/assets survive an uninstall.
     */
    public function test_no_published_installer_files_remain_in_the_repository(): void
    {
        $this->assertFileDoesNotExist(config_path('installer.php'));
        $this->assertFileDoesNotExist(resource_path('views/vendor/installer'));
        $this->assertFileDoesNotExist(public_path('installer'));
        $this->assertFileDoesNotExist(public_path('assets/js/installer.js'));
    }

    /**
     * Belt and braces for the above: even if a dependency re-registers those
     * prefixes, the route table this application builds must not contain them.
     */
    public function test_the_route_table_contains_no_wizard_prefixes(): void
    {
        $offenders = [];

        foreach (Route::getRoutes() as $route) {
            $uri = ltrim((string) $route->uri(), '/');
            foreach (['install', 'update', 'envato'] as $prefix) {
                if ($uri === $prefix || str_starts_with($uri, $prefix.'/')) {
                    $offenders[] = $uri;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($offenders)), 'wizard route prefixes must not be registered');
    }

    /**
     * tinker is a REPL with full application access. Shipping it in the
     * production image means `php artisan tinker` works on the box that holds
     * the database credentials. It is a dev tool and belongs in require-dev,
     * which the Dockerfile drops via dump-autoload --no-dev.
     */
    public function test_tinker_is_a_dev_dependency_only(): void
    {
        $composer = $this->composerManifest();

        $this->assertArrayNotHasKey('laravel/tinker', $composer['require'] ?? []);
        $this->assertArrayHasKey('laravel/tinker', $composer['require-dev'] ?? []);

        $lockedInProd = array_column($this->composerLock()['packages'] ?? [], 'name');
        $this->assertNotContains('laravel/tinker', $lockedInProd, 'tinker must not be in the production package set');
        $this->assertNotContains('psy/psysh', $lockedInProd, 'psysh is tinker\'s REPL and must not ship to production');
    }

    /**
     * GHSA-63hm-g96r-2m2q affects laravel/framework < 12.71.0. The lock is the
     * thing that decides what gets installed, so assert the lock, not the
     * open-ended ^12.0 constraint in composer.json.
     */
    public function test_locked_framework_version_is_patched(): void
    {
        $version = $this->lockedVersion('laravel/framework', prod: true);

        $this->assertNotNull($version, 'laravel/framework must be locked');
        $this->assertTrue(
            version_compare(ltrim((string) $version, 'v'), '12.71.0', '>='),
            "locked laravel/framework is $version, need >= 12.71.0"
        );
    }

    /**
     * vite 3 pulled esbuild <= 0.24.2 (GHSA-67mh-4wv8-2f99, moderate) and vite
     * itself was <= 6.4.3 (high): the dev server answers cross-origin requests
     * and leaks what it reads. `npm audit fix --force` "solves" such a finding by
     * downgrading across a major, so the floors this project controls are
     * asserted here instead of left to whoever runs audit next.
     */
    public function test_locked_build_tooling_is_outside_the_advisory_ranges(): void
    {
        $vite = $this->npmLockedVersion('vite');
        $this->assertNotNull($vite, 'vite must be locked');
        $this->assertTrue(
            version_compare((string) $vite, '7.0.0', '>='),
            "locked vite is $vite; the advisory range is <= 6.4.3"
        );

        $esbuild = $this->npmLockedVersion('esbuild');
        $this->assertNotNull($esbuild, 'esbuild must be locked');
        $this->assertTrue(
            version_compare((string) $esbuild, '0.24.2', '>'),
            "locked esbuild is $esbuild; the advisory range is <= 0.24.2"
        );

    }

    /**
     * The Laravel-side vite bundle had no consumer in either deployment path.
     * Blade layouts load static files through asset('css/...') and
     * asset('assets/...') — no view calls @vite or vite(), nothing references
     * /build/ — and `Dockerfile` has no node step, so the Render image never
     * contained the output even if something had pointed at it. The Vercel SPA
     * builds from the root index.html + src/ through vite.config.vercel.js,
     * which imports no Laravel plugin; src/ imports no npm package at all.
     *
     * So the bundle went together with the nine packages only it used, and with
     * them @grpc/grpc-js: firebase was the sole reason the override existed
     * (@firebase/firestore nests <= 1.13.5 — GHSA-m9gg-hp2v-232j and
     * GHSA-f596-whhp-79r4, both high). Dropping firebase removes the advisory
     * instead of pinning around it. 191 packages left the tree and the SPA build
     * output hashes were byte-identical before and after, which is the evidence
     * that nothing consumed what was deleted.
     *
     * This asserts the removal holds. Re-adding a frontend that nothing serves
     * is how the tree grew back to 219 packages in the first place.
     */
    public function test_the_unconsumed_frontend_bundle_and_its_tree_stay_gone(): void
    {
        $package = $this->npmManifest();
        $lock = $this->npmLock();

        $this->assertArrayNotHasKey('overrides', $package, 'the @grpc override existed only for firebase, and firebase is gone');
        $this->assertArrayNotHasKey('build:laravel', $package['scripts'] ?? [], 'there is no second frontend to build');

        $dead = ['firebase', 'laravel-vite-plugin', 'tailwindcss', 'postcss', 'autoprefixer', '@tailwindcss/forms', 'alpinejs', 'axios', 'lodash'];
        foreach ($dead as $name) {
            $this->assertArrayNotHasKey($name, $package['dependencies'] ?? [], "$name was used only by the removed bundle");
            $this->assertArrayNotHasKey($name, $package['devDependencies'] ?? [], "$name was used only by the removed bundle");
        }

        foreach (array_keys($lock['packages'] ?? []) as $path) {
            $this->assertStringNotContainsString('node_modules/firebase', (string) $path, 'firebase must not re-enter the lock');
            $this->assertStringNotContainsString('@grpc/grpc-js', (string) $path, 'grpc arrived through firebase only');
            $this->assertStringNotContainsString('laravel-vite-plugin', (string) $path);
        }

        $this->assertFileDoesNotExist(base_path('vite.config.js'));
        $this->assertDirectoryDoesNotExist(resource_path('js'));
        $this->assertDirectoryDoesNotExist(resource_path('css'));

        // Neither config ever existed, which is the tell that those packages
        // were never wired to anything.
        $this->assertFileDoesNotExist(base_path('tailwind.config.js'));
        $this->assertFileDoesNotExist(base_path('postcss.config.js'));
    }

    /**
     * dataconnect/ and src/dataconnect-generated/ were the Firebase Data Connect
     * quickstart sample: schema.gql is literally headed "Example schema for
     * simple movie review app" and dataconnect.yaml points at a Cloud SQL
     * instance (www-fdc, us-central1) unrelated to this project, which runs on
     * Supabase in Frankfurt. Nothing outside those directories imported them. A
     * generated client with a live-looking endpoint sitting in a public
     * repository is an invitation rather than a harmless leftover.
     */
    public function test_the_data_connect_sample_is_not_in_the_tree(): void
    {
        $this->assertDirectoryDoesNotExist(base_path('dataconnect'));
        $this->assertDirectoryDoesNotExist(base_path('src/dataconnect-generated'));

        foreach (array_keys($this->npmLock()['packages'] ?? []) as $path) {
            $this->assertStringNotContainsString('dataconnect', (string) $path);
        }
    }

    /**
     * `Modules/Landing` is enabled and serves 48 Blade views, but it shipped its
     * own frontend build that nothing ran and nothing could have run:
     * `vite.config.js` declared two inputs and one of them
     * (`resources/assets/js/app.js`) does not exist, `public/build-landing/` was
     * never produced, and the only mention of `build-landing` anywhere in the
     * repository was that config file itself. No view calls `@vite` — the module
     * loads `asset('assets/web/js/…')` and `asset('assets/js/…')`, static files
     * that are tracked under `public/assets/`, exactly like the core layouts.
     *
     * The reason this is a security item and not just tidiness: its manifest
     * pinned **vite ^4.0.0**, inside the `<= 6.4.3` high-severity range, with
     * `laravel-vite-plugin ^0.7.5` and **no lockfile**. Root `npm ci` never
     * installed it, so root `npm audit` never saw it — an advisory-range build
     * tool parked where no scanner looks, waiting for whoever runs `npm install`
     * in that directory next.
     */
    public function test_no_module_ships_its_own_unaudited_frontend_build(): void
    {
        $this->assertFileDoesNotExist(base_path('Modules/Landing/package.json'));
        $this->assertFileDoesNotExist(base_path('Modules/Landing/vite.config.js'));

        // Generalised: the root manifest is the only npm build definition in the
        // application tree, so it is the only one CI installs and audits.
        foreach (glob(base_path('Modules/*/package.json')) ?: [] as $stray) {
            $this->fail("a module ships its own npm manifest outside the audited root: $stray");
        }

        foreach (glob(base_path('Modules/*/vite.config.js')) ?: [] as $stray) {
            $this->fail("a module ships its own vite config outside the audited root: $stray");
        }
    }

    /**
     * Nothing in the tree imports these. @vercel/analytics appeared only in its
     * own manifest line, and @dataconnect/generated is the generated client for
     * the removed Data Connect sample. Unused dependencies are installed,
     * patched and audited for no reason.
     */
    public function test_unused_runtime_packages_are_not_declared(): void
    {
        $package = $this->npmManifest();
        $dependencies = $package['dependencies'] ?? [];

        $this->assertArrayNotHasKey('@vercel/analytics', $dependencies);
        $this->assertArrayNotHasKey('@dataconnect/generated', $dependencies);
        $this->assertArrayNotHasKey('@grpc/grpc-js', $dependencies, 'grpc is never a direct dependency');
    }

    /**
     * vite 7 requires node ^20.19.0 || >=22.12.0. Without an explicit engines
     * field the build host picks a default, and a default change becomes a
     * build failure with no commit to explain it.
     */
    public function test_the_node_runtime_is_pinned(): void
    {
        $engines = $this->npmManifest()['engines'] ?? [];

        $this->assertArrayHasKey('node', $engines);
        $this->assertMatchesRegularExpression('/^>=\s*2[2-9]\./', (string) $engines['node'], 'node floor must satisfy vite 7');
    }

    /**
     * This repository is public. public/uploads held 109 runtime files (19 MB)
     * and public/demo_images 93 more — prescriptions and tenant images in a
     * place where anyone with the URL could read them and every clone
     * downloaded them. Both directories are now untracked; verified safe on
     * 2026-11-13 by scanning every text/json/jsonb column of every public table
     * in production for references to either path (zero rows).
     */
    public function test_user_uploads_are_not_tracked(): void
    {
        $ignore = (string) file_get_contents(base_path('.gitignore'));

        foreach (['/public/uploads', '/public/demo_images'] as $path) {
            $this->assertStringContainsString(
                $path,
                $ignore,
                "$path must stay ignored — user content does not belong in a public repository"
            );
        }
    }

    /**
     * SecurityCheck filtered every request input through a regex blacklist and
     * wrote the result back with $request->merge(), so a product description
     * containing "select" or a note containing "<" was silently truncated before
     * it reached the database. It only ever *logged* SQL injection and its
     * abort(403) was commented out — data corruption with no security gain.
     */
    public function test_the_request_mutating_security_middleware_is_gone(): void
    {
        $this->assertFalse(
            class_exists(\App\Http\Middleware\SecurityCheck::class),
            'SecurityCheck must not come back: it rewrote request input from a blacklist'
        );
        $this->assertFileDoesNotExist(app_path('Http/Middleware/SecurityCheck.php'));

        $kernel = new \ReflectionClass(\App\Http\Kernel::class);
        $groups = $kernel->getDefaultProperties()['middlewareGroups'] ?? [];

        foreach ($groups as $group => $middleware) {
            foreach ($middleware as $entry) {
                $this->assertStringNotContainsString(
                    'SecurityCheck',
                    is_string($entry) ? $entry : '',
                    "the '$group' group must not contain SecurityCheck"
                );
            }
        }
    }

    /**
     * escapeString() was addslashes(). That is not SQL escaping — it does not
     * know the connection charset and does not escape identifiers — and leaving
     * a method with that name inside a class called SecurityService is an
     * invitation to reintroduce injection. Every query in this application uses
     * the builder or bound parameters.
     */
    public function test_the_addslashes_helper_is_gone(): void
    {
        $this->assertFalse(
            method_exists(SecurityService::class, 'escapeString'),
            'escapeString() was addslashes() and must not return'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function composerManifest(): array
    {
        return json_decode((string) file_get_contents(base_path('composer.json')), true) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function composerLock(): array
    {
        return json_decode((string) file_get_contents(base_path('composer.lock')), true) ?? [];
    }

    private function lockedVersion(string $package, bool $prod): ?string
    {
        $section = $prod ? 'packages' : 'packages-dev';

        foreach ($this->composerLock()[$section] ?? [] as $entry) {
            if (($entry['name'] ?? null) === $package) {
                return $entry['version'] ?? null;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function npmManifest(): array
    {
        return json_decode((string) file_get_contents(base_path('package.json')), true) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function npmLock(): array
    {
        return json_decode((string) file_get_contents(base_path('package-lock.json')), true) ?? [];
    }

    private function npmLockedVersion(string $package): ?string
    {
        $entry = ($this->npmLock()['packages'] ?? [])['node_modules/'.$package] ?? null;

        return $entry['version'] ?? null;
    }
}
