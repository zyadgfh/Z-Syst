<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * E2E tests verifying different user roles can access admin pages.
 * Tests the full request cycle through middleware + policy authorization.
 */
class AdminRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $this->superAdmin = User::where('email', 'superadmin@z-syst.com')->first();
        $this->admin = User::where('email', 'admin@test.com')->first();
        $this->staff = User::where('email', 'staff@test.com')->first();
    }

    // =========================================================================
    // Dashboard Access
    // =========================================================================

    public function test_super_admin_can_access_dashboard(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard.index'))
            ->assertOk();
    }

    public function test_unauthenticated_user_redirected_from_admin(): void
    {
        $this->get(route('admin.dashboard.index'))
            ->assertRedirect();
    }

    // =========================================================================
    // CRUD Access — Business Categories
    // =========================================================================

    public function test_admin_can_view_business_categories(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.business-categories.index'))
            ->assertOk();
    }

    public function test_staff_cannot_view_business_categories_without_permission(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.business-categories.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_business_category_create(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.business-categories.create'))
            ->assertOk();
    }

    // =========================================================================
    // CRUD Access — Currencies
    // =========================================================================

    public function test_admin_can_view_currencies(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.currencies.index'))
            ->assertOk();
    }

    public function test_staff_cannot_view_currencies_without_permission(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.currencies.index'))
            ->assertForbidden();
    }

    // =========================================================================
    // CRUD Access — Plans
    // =========================================================================

    public function test_admin_can_view_plans(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.plans.index'))
            ->assertOk();
    }

    public function test_staff_cannot_view_plans_without_permission(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.plans.index'))
            ->assertForbidden();
    }

    // =========================================================================
    // CRUD Access — Users
    // =========================================================================

    public function test_admin_can_view_users(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_staff_can_view_users_with_permission(): void
    {
        // Staff has users-read permission
        $this->actingAs($this->staff)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_admin_can_view_user_create(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertOk();
    }

    // =========================================================================
    // CRUD Access — Roles
    // =========================================================================

    public function test_admin_can_view_roles(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    public function test_staff_cannot_view_roles_without_permission(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    // =========================================================================
    // CRUD Access — Coupons
    // =========================================================================

    public function test_admin_can_view_coupons(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.coupons.index'))
            ->assertOk();
    }

    public function test_staff_cannot_view_coupons_without_permission(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.coupons.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_coupon_create(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.coupons.create'))
            ->assertOk();
    }

    // =========================================================================
    // Unauthenticated Cannot Access Any Admin Page
    // =========================================================================

    public function test_unauthenticated_cannot_view_business_categories(): void
    {
        $this->get(route('admin.business-categories.index'))
            ->assertRedirect();
    }

    public function test_unauthenticated_cannot_view_coupons(): void
    {
        $this->get(route('admin.coupons.index'))
            ->assertRedirect();
    }

    public function test_unauthenticated_cannot_view_users(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect();
    }

    // =========================================================================
    // No-Role User Cannot Access Admin
    // =========================================================================

    public function test_user_without_role_cannot_access_admin(): void
    {
        $noRole = User::factory()->create([
            'role' => 'customer',
            'business_id' => null,
        ]);

        // No-role user gets redirected (middleware blocks access)
        $this->actingAs($noRole)
            ->get(route('admin.dashboard.index'))
            ->assertRedirect();
    }

    // =========================================================================
    // Permission Granularity — Create vs Read
    // =========================================================================

    public function test_user_with_read_only_permission_cannot_create(): void
    {
        $staffWithRead = User::factory()->create(['role' => 'staff']);
        $staffWithRead->assignRole('Staff');

        // Staff has products-view but not products-create
        $this->actingAs($staffWithRead)
            ->get(route('admin.users.create'))
            ->assertForbidden();
    }
}
