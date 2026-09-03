<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Verifies all datas.blade.php partials have proper @can permission
 * guards around edit and delete actions.
 */
class DatasPermissionGuardTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Static data
    // =========================================================================

    /**
     * @return array<string, array{0: string, 1: array}>
     */
    public static function allDatasFilesProvider(): array
    {
        $partials = [
            'banners' => 'resources/views/admin/banners/datas.blade.php',
            'business' => 'resources/views/admin/business/datas.blade.php',
            'business-categories' => 'resources/views/admin/business-categories/datas.blade.php',
            'currencies' => 'resources/views/admin/currencies/datas.blade.php',
            'plans' => 'resources/views/admin/plans/datas.blade.php',
            'prescriptions' => 'resources/views/admin/prescriptions/datas.blade.php',
            'users' => 'resources/views/admin/users/datas.blade.php',
            'notifications' => 'resources/views/admin/notifications/datas.blade.php',
            'manual-payments' => 'resources/views/admin/manual-payments/datas.blade.php',
            'subscribers' => 'resources/views/admin/subscribers/datas.blade.php',
            'features' => 'Modules/Landing/resources/views/admin/features/datas.blade.php',
            'testimonials' => 'Modules/Landing/resources/views/admin/testimonials/datas.blade.php',
            'interfaces' => 'Modules/Landing/resources/views/admin/interfaces/datas.blade.php',
            'messages' => 'Modules/Landing/resources/views/admin/messages/datas.blade.php',
            'blogs' => 'Modules/Landing/resources/views/admin/blogs/datas.blade.php',
            'blogs-comment' => 'Modules/Landing/resources/views/admin/blogs/comment/datas.blade.php',
        ];

        return array_map(
            fn($path) => [$path],
            $partials,
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function editRouteProvider(): array
    {
        return [
            'banners'            => ['resources/views/admin/banners/datas.blade.php',              'admin.banners.edit',             'banners-update'],
            'business'           => ['resources/views/admin/business/datas.blade.php',             'admin.business.edit',            'business-update'],
            'business-categories'=> ['resources/views/admin/business-categories/datas.blade.php',   'admin.business-categories.edit', 'business-categories-update'],
            'currencies'         => ['resources/views/admin/currencies/datas.blade.php',            'admin.currencies.edit',          'currencies-update'],
            'plans'              => ['resources/views/admin/plans/datas.blade.php',                 'admin.plans.edit',               'plans-update'],
            'prescriptions'      => ['resources/views/admin/prescriptions/datas.blade.php',         'admin.prescriptions.edit',       'prescriptions-update'],
            'users'              => ['resources/views/admin/users/datas.blade.php',                 'admin.users.edit',               'users-update'],
            'features'           => ['Modules/Landing/resources/views/admin/features/datas.blade.php',        'admin.features.edit',        'features-update'],
            'testimonials'       => ['Modules/Landing/resources/views/admin/testimonials/datas.blade.php',    'admin.testimonials.edit',    'testimonials-update'],
            'interfaces'         => ['Modules/Landing/resources/views/admin/interfaces/datas.blade.php',      'admin.interfaces.edit',      'interfaces-update'],
            'blogs'              => ['Modules/Landing/resources/views/admin/blogs/datas.blade.php',           'admin.blogs.edit',            'blogs-update'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function deleteRouteProvider(): array
    {
        return [
            'banners'            => ['resources/views/admin/banners/datas.blade.php',              'admin.banners.destroy',          'banners-delete'],
            'business'           => ['resources/views/admin/business/datas.blade.php',             'admin.business.destroy',         'business-delete'],
            'business-categories'=> ['resources/views/admin/business-categories/datas.blade.php',   'admin.business-categories.destroy', 'business-categories-delete'],
            'currencies'         => ['resources/views/admin/currencies/datas.blade.php',            'admin.currencies.destroy',       'currencies-delete'],
            'plans'              => ['resources/views/admin/plans/datas.blade.php',                 'admin.plans.destroy',            'plans-delete'],
            'prescriptions'      => ['resources/views/admin/prescriptions/datas.blade.php',         'admin.prescriptions.destroy',    'prescriptions-delete'],
            'users'              => ['resources/views/admin/users/datas.blade.php',                 'admin.users.destroy',            'users-delete'],
            'features'           => ['Modules/Landing/resources/views/admin/features/datas.blade.php',        'admin.features.destroy',     'features-delete'],
            'testimonials'       => ['Modules/Landing/resources/views/admin/testimonials/datas.blade.php',    'admin.testimonials.destroy', 'testimonials-delete'],
            'interfaces'         => ['Modules/Landing/resources/views/admin/interfaces/datas.blade.php',      'admin.interfaces.destroy',   'interfaces-delete'],
            'messages'           => ['Modules/Landing/resources/views/admin/messages/datas.blade.php',        'admin.messages.destroy',      'messages-delete'],
            'blogs'              => ['Modules/Landing/resources/views/admin/blogs/datas.blade.php',           'admin.blogs.destroy',         'blogs-delete'],
            'blogs-comment'      => ['Modules/Landing/resources/views/admin/blogs/comment/datas.blade.php',   'admin.comments.destroy',      'blogs-delete'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function indexMultiDeleteProvider(): array
    {
        return [
            'banners'            => ['resources/views/admin/banners/index.blade.php',              'banners-delete'],
            'business'           => ['resources/views/admin/business/index.blade.php',             'business-delete'],
            'business-categories'=> ['resources/views/admin/business-categories/index.blade.php',   'business-categories-delete'],
            'plans'              => ['resources/views/admin/plans/index.blade.php',                 'plans-delete'],
            'currencies'         => ['resources/views/admin/currencies/index.blade.php',            'currencies-delete'],
            'users'              => ['resources/views/admin/users/index.blade.php',                 'users-delete'],
            'features'           => ['Modules/Landing/resources/views/admin/features/index.blade.php',     'features-delete'],
            'messages'           => ['Modules/Landing/resources/views/admin/messages/index.blade.php',     'messages-delete'],
            'testimonials'       => ['Modules/Landing/resources/views/admin/testimonials/index.blade.php', 'testimonials-delete'],
        ];
    }

    // =========================================================================
    // File Existence
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('allDatasFilesProvider')]
    public function test_datas_file_exists(string $path): void
    {
        $this->assertFileExists(base_path($path), "Missing: {$path}");
    }

    // =========================================================================
    // @can guards on edit links
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('editRouteProvider')]
    public function test_edit_action_has_can_guard(string $path, string $editRoute, string $permission): void
    {
        $content = $this->readFile($path);
        $hasEditLink = str_contains($content, $editRoute);

        if ($hasEditLink) {
            $this->assertTrue(
                str_contains($content, "@can('{$permission}')"),
                "[{$path}] Edit route '{$editRoute}' NOT wrapped in @can('{$permission}')"
            );
        }
    }

    // =========================================================================
    // @can guards on delete links
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('deleteRouteProvider')]
    public function test_delete_action_has_can_guard(string $path, string $deleteRoute, string $permission): void
    {
        $content = $this->readFile($path);
        $hasDeleteLink = str_contains($content, $deleteRoute);

        if ($hasDeleteLink) {
            $this->assertTrue(
                str_contains($content, "@can('{$permission}')"),
                "[{$path}] Delete route '{$deleteRoute}' NOT wrapped in @can('{$permission}')"
            );
        }
    }

    // =========================================================================
    // @can / @endcan balance
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('allDatasFilesProvider')]
    public function test_can_endcan_blocks_are_balanced(string $path): void
    {
        $content = $this->readFile($path);

        preg_match_all('/@can\b/', $content, $opens);
        preg_match_all('/@endcan\b/', $content, $closes);

        $this->assertEquals(
            count($opens[0]),
            count($closes[0]),
            "[{$path}] @can (" . count($opens[0]) . ") vs @endcan (" . count($closes[0]) . ") mismatch"
        );
    }

    // =========================================================================
    // Permissions defined in seeder
    // =========================================================================

    public function test_all_datas_permissions_are_seeded(): void
    {
        $seederContent = file_get_contents(base_path('database/seeders/PermissionSeeder.php'));

        $allPermissions = [
            'banners-update', 'banners-delete',
            'business-update', 'business-delete',
            'business-categories-update', 'business-categories-delete',
            'currencies-update', 'currencies-delete',
            'plans-update', 'plans-delete',
            'prescriptions-update', 'prescriptions-delete',
            'users-update', 'users-delete',
            'notifications-read',
            'manual-payment-reports-read',
            'subscription-reports-read',
            'features-update', 'features-delete',
            'testimonials-update', 'testimonials-delete',
            'interfaces-update', 'interfaces-delete',
            'messages-delete',
            'blogs-update', 'blogs-delete',
            'coupons-update', 'coupons-delete',
            'gateways-edit', 'gateways-delete',
        ];

        foreach ($allPermissions as $permission) {
            $this->assertStringContainsString(
                "'{$permission}'",
                $seederContent,
                "Permission '{$permission}' NOT defined in PermissionSeeder"
            );
        }
    }

    // =========================================================================
    // Permissions exist in database after seeding
    // =========================================================================

    public function test_all_datas_permissions_exist_in_database(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $permissions = [
            'banners-update', 'banners-delete',
            'business-update', 'business-delete',
            'business-categories-update', 'business-categories-delete',
            'currencies-update', 'currencies-delete',
            'plans-update', 'plans-delete',
            'prescriptions-update', 'prescriptions-delete',
            'users-update', 'users-delete',
            'notifications-read',
            'manual-payment-reports-read',
            'subscription-reports-read',
            'features-update', 'features-delete',
            'testimonials-update', 'testimonials-delete',
            'interfaces-update', 'interfaces-delete',
            'messages-delete',
            'blogs-update', 'blogs-delete',
            'coupons-update', 'coupons-delete',
            'gateways-edit', 'gateways-delete',
        ];

        foreach ($permissions as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' missing from database"
            );
        }
    }

    // =========================================================================
    // Index view multi-delete guards
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\DataProvider('indexMultiDeleteProvider')]
    public function test_index_multi_delete_has_can_guard(string $path, string $permission): void
    {
        if (!file_exists(base_path($path))) {
            $this->markTestSkipped("File not found: {$path}");
        }

        $content = $this->readFile($path);

        if (str_contains($content, 'multi-delete-modal') || str_contains($content, 'delete-all')) {
            $this->assertTrue(
                str_contains($content, "@can('{$permission}')"),
                "[{$path}] Multi-delete button NOT wrapped in @can('{$permission}')"
            );
        }
    }

    // =========================================================================
    // Non-datas view @can guard tests
    // =========================================================================

    /**
     * Non-datas views that have edit/delete links and their expected permissions.
     *
     * @return array<string, array{0: string, 1: string|null, 2: string|null, 3: string|null, 4: string|null}>
     *         [path, editRoute, editPermission, deleteRoute, deletePermission]
     */
    public static function nonDatasEditRouteProvider(): array
    {
        return [
            'coupons'              => ['resources/views/admin/coupons/index.blade.php',              'admin.coupons.edit',              'coupons-update',              null,                          null],
            'payment-gateways'     => ['resources/views/admin/payment-gateways/index.blade.php',     'admin.payment-gateways.edit',     'gateways-edit',               null,                          null],
            'purchase-orders-show' => ['resources/views/admin/purchase-orders/show.blade.php',       'admin.purchase-orders.edit',      'purchases-edit',              null,                          null],
            'suppliers-show'       => ['resources/views/admin/suppliers/show.blade.php',            'admin.suppliers.edit',            'suppliers-edit',              null,                          null],
            'insurance-policies'   => ['resources/views/admin/insurance/policies/index.blade.php',   'admin.insurance.policies.edit',   'insurance-policies-update',   null,                          null],
            'insurance-companies'  => ['resources/views/admin/insurance/companies/index.blade.php',  'admin.insurance.companies.edit',  'insurance-companies-update',  null,                          null],
            'loyalty-programs'     => ['resources/views/admin/loyalty/programs.blade.php',           'admin.loyalty.edit',              'loyalty-update',              'admin.loyalty.destroy',        'loyalty-delete'],
            'warehouses'           => ['resources/views/admin/warehouses/index.blade.php',           'admin.warehouses.edit',           'warehouses-update',           'admin.warehouses.destroy',     'warehouses-delete'],
            'roles'                => ['resources/views/admin/roles/index.blade.php',                'admin.roles.edit',                'roles-update',                null,                          null],
            'products'             => ['resources/views/admin/products/index.blade.php',             'admin.items.edit',                'update',                      'admin.items.destroy',          'delete'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonDatasEditRouteProvider')]
    public function test_non_datas_edit_action_has_can_guard(string $path, ?string $editRoute, ?string $editPermission, ?string $deleteRoute, ?string $deletePermission): void
    {
        if (!file_exists(base_path($path))) {
            $this->markTestSkipped("File not found: {$path}");
        }

        $content = $this->readFile($path);

        if ($editRoute && $editPermission) {
            if (str_contains($content, $editRoute)) {
                // Accept: @can('perm'), @can('perm', $obj), auth()->user()->can('perm'), Gate::allows('perm')
                $hasGuard =
                    str_contains($content, "@can('{$editPermission}'") ||
                    str_contains($content, "auth()->user()->can('{$editPermission}')") ||
                    str_contains($content, "Gate::allows('{$editPermission}'") ||
                    str_contains($content, "\$user->can('{$editPermission}'");

                $this->assertTrue(
                    $hasGuard,
                    "[{$path}] Edit route '{$editRoute}' NOT guarded by @can('{$editPermission}') or auth()->user()->can()"
                );
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonDatasEditRouteProvider')]
    public function test_non_datas_delete_action_has_can_guard(string $path, ?string $editRoute, ?string $editPermission, ?string $deleteRoute, ?string $deletePermission): void
    {
        if (!file_exists(base_path($path))) {
            $this->markTestSkipped("File not found: {$path}");
        }

        $content = $this->readFile($path);

        if ($deleteRoute && $deletePermission) {
            if (str_contains($content, $deleteRoute)) {
                $hasGuard =
                    str_contains($content, "@can('{$deletePermission}'") ||
                    str_contains($content, "auth()->user()->can('{$deletePermission}')") ||
                    str_contains($content, "Gate::allows('{$deletePermission}'");

                $this->assertTrue(
                    $hasGuard,
                    "[{$path}] Delete route '{$deleteRoute}' NOT guarded by @can('{$deletePermission}') or auth()->user()->can()"
                );
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonDatasEditRouteProvider')]
    public function test_non_datas_can_endcan_balanced(string $path, ?string $editRoute, ?string $editPermission, ?string $deleteRoute, ?string $deletePermission): void
    {
        if (!file_exists(base_path($path))) {
            $this->markTestSkipped("File not found: {$path}");
        }

        $content = $this->readFile($path);

        preg_match_all('/@can\b/', $content, $opens);
        preg_match_all('/@endcan\b/', $content, $closes);

        $this->assertEquals(
            count($opens[0]),
            count($closes[0]),
            "[{$path}] @can (" . count($opens[0]) . ") vs @endcan (" . count($closes[0]) . ") mismatch"
        );
    }

    // =========================================================================
    // Policies registered in AuthServiceProvider
    // =========================================================================

    public function test_all_admin_policies_are_registered(): void
    {
        $authService = file_get_contents(base_path('app/Providers/AuthServiceProvider.php'));

        $expectedPolicies = [
            'Banner' => 'BannerPolicy',
            'Business' => 'BusinessPolicy',
            'BusinessCategory' => 'BusinessCategoryPolicy',
            'Currency' => 'CurrencyPolicy',
            'Plan' => 'PlanPolicy',
            'Coupon' => 'CouponPolicy',
            'User' => 'UserPolicy',
            'Role' => 'RolePolicy',
        ];

        foreach ($expectedPolicies as $model => $policy) {
            $this->assertStringContainsString(
                "{$model}::class => {$policy}::class",
                $authService,
                "Policy mapping {$model} → {$policy} not registered in AuthServiceProvider"
            );
        }
    }

    public function test_all_policy_files_exist(): void
    {
        $policies = [
            'BannerPolicy',
            'BusinessPolicy',
            'BusinessCategoryPolicy',
            'CurrencyPolicy',
            'PlanPolicy',
            'CouponPolicy',
            'UserPolicy',
            'RolePolicy',
        ];

        foreach ($policies as $policy) {
            $this->assertFileExists(
                base_path("app/Policies/{$policy}.php"),
                "Policy file missing: {$policy}.php"
            );
        }
    }

    // =========================================================================
    // Security: No unescaped user input in Blade views
    // =========================================================================

    public function test_no_raw_user_input_in_admin_views(): void
    {
        $adminViews = glob(resource_path('views/admin/**/*.blade.php'));
        $landingViews = glob(resource_path('../Modules/Landing/resources/views/admin/**/*.blade.php'));
        $allViews = array_merge($adminViews, $landingViews);

        $risky = [];
        foreach ($allViews as $view) {
            $content = file_get_contents($view);
            // Find {!! !!} that contain user-input variables (request, input, old())
            if (preg_match_all('/\{\{!!\s*(request\(|\$request->|old\(|\$_)/', $content, $matches)) {
                $relativePath = str_replace(base_path() . '/', '', $view);
                $risky[] = "{$relativePath}: " . implode(', ', $matches[0]);
            }
        }

        $this->assertEmpty(
            $risky,
            "Admin views contain {!! !!} with raw user input (XSS risk):\n" . implode("\n", $risky)
        );
    }

    // =========================================================================
    // Security: All POST/PUT/DELETE forms have CSRF protection
    // =========================================================================

    public function test_all_admin_forms_have_csrf_protection(): void
    {
        $adminViews = glob(resource_path('views/admin/**/*.blade.php'));
        $landingViews = glob(resource_path('../Modules/Landing/resources/views/admin/**/*.blade.php'));
        $allViews = array_merge($adminViews, $landingViews);

        $missing = [];
        foreach ($allViews as $view) {
            $content = file_get_contents($view);
            $relativePath = str_replace(base_path() . '/', '', $view);

            // Check for <form with method POST/PUT/DELETE that has NO @csrf and NO confirm-action (AJAX)
            if (preg_match_all('/<form[^>]*method\s*=\s*["\'](?:POST|PUT|DELETE|PATCH)["\']/i', $content, $forms)) {
                $hasCsrf = str_contains($content, '@csrf') || str_contains($content, 'csrf_token') || str_contains($content, 'csrf-field');
                $hasAjaxHandler = str_contains($content, 'confirm-action') || str_contains($content, 'ajaxform') || str_contains($content, 'X-CSRF-TOKEN');

                if (!$hasCsrf && !$hasAjaxHandler) {
                    $missing[] = $relativePath;
                }
            }
        }

        $this->assertEmpty(
            $missing,
            "Admin forms without CSRF protection (no @csrf and no AJAX handler):\n" . implode("\n", $missing)
        );
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private function readFile(string $path): string
    {
        $fullPath = base_path($path);
        $this->assertFileExists($fullPath, "File not found: {$path}");
        return file_get_contents($fullPath);
    }
}
