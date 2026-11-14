<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_without_manage_pages_settings(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Every Blade view in this application — the core ones and the Landing
     * module's — loads its CSS, JS and images through `asset('…')`, which
     * resolves against `public/`. Nothing is served from a vite bundle; that is
     * why the Laravel-side bundle could be deleted (RUNBOOK §10.8).
     *
     * That makes `public/` the contract, and the contract was already broken in
     * two places on the live public site: `plan-check.svg` and `plan-cross.svg`
     * — the tick and cross on the pricing comparison table — existed only under
     * `Modules/Landing/resources/assets/web/images/banner/` and were never
     * published to `public/`, so both rendered as broken images. A third
     * fallback, `assets/img/demo-img.png`, did not exist anywhere in the
     * repository at all; it now points at the tracked `assets/img/icon/
     * no-image.svg`.
     *
     * A missing static asset is invisible to every other test here: the page
     * still returns 200 and the HTML still looks right. This is the only thing
     * that catches it, and it scans the source rather than one route so a new
     * view is covered the moment it is written.
     */
    public function test_every_static_asset_a_blade_view_names_actually_exists(): void
    {
        $roots = [resource_path('views')];
        foreach (glob(base_path('Modules/*/resources/views')) ?: [] as $moduleViews) {
            $roots[] = $moduleViews;
        }

        $referenced = [];
        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                preg_match_all(
                    "/asset\(\s*'([^'$]+)'\s*\)/",
                    (string) file_get_contents($file->getPathname()),
                    $matches
                );
                foreach ($matches[1] as $path) {
                    // Dynamic paths (asset($blog->image)) are a runtime concern.
                    $referenced[$path] = true;
                }
            }
        }

        $this->assertNotEmpty($referenced, 'expected the views to name static assets');

        $missing = [];
        foreach (array_keys($referenced) as $path) {
            if (! is_file(public_path($path))) {
                $missing[] = $path;
            }
        }

        sort($missing);
        $this->assertSame([], $missing, 'these assets are referenced by a Blade view but missing from public/');
    }
}
