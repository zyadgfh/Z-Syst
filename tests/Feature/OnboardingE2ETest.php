<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingE2ETest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $staff;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create([
            'business_category_id' => $category->id,
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'shop-owner',
        ]);

        $this->staff = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'staff',
        ]);

        // Seed permissions
        $perms = ['dashboard-read', 'products-view', 'products-create', 'products-edit', 'products-delete',
            'business-categories-read', 'business-categories-create', 'business-categories-update', 'business-categories-delete'];
        foreach ($perms as $p) {
            \Spatie\Permission\Models\Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Create roles with permissions
        $role = \Spatie\Permission\Models\Role::create(['name' => 'shop-owner', 'guard_name' => 'web']);
        $role->syncPermissions($perms);
        $this->owner->assignRole('shop-owner');

        $staffRole = \Spatie\Permission\Models\Role::create(['name' => 'staff', 'guard_name' => 'web']);
        $staffRole->syncPermissions(['dashboard-read', 'products-view', 'business-categories-read']);
        $this->staff->assignRole('staff');

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ─── Registration ──────────────────────────────────────

    public function test_register_page_loads(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Create Your Account');
    }

    public function test_register_page_has_required_fields(): void
    {
        $response = $this->get('/register');
        $response->assertSee('companyName');
        $response->assertSee('email');
        $response->assertSee('phoneNumber');
        $response->assertSee('business_category_id');
        $response->assertSee('password');
    }

    public function test_register_page_links_to_login(): void
    {
        $response = $this->get('/register');
        $response->assertSee(route('login'));
    }

    // ─── Onboarding Wizard ─────────────────────────────────

    public function test_onboarding_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.onboarding.index'));
        $response->assertStatus(200);
        $response->assertSee('Welcome to Z-Syst');
    }

    public function test_onboarding_step1_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.onboarding.step', 1));
        $response->assertStatus(200);
        $response->assertSee('Business Information');
    }

    public function test_onboarding_step2_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.onboarding.step', 2));
        $response->assertStatus(200);
        $response->assertSee('Product Categories');
    }

    public function test_onboarding_step3_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.onboarding.step', 3));
        $response->assertStatus(200);
        $response->assertSee('Add Your First Product');
    }

    public function test_onboarding_step4_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.onboarding.step', 4));
        $response->assertStatus(200);
        $response->assertSee('Review');
    }

    public function test_onboarding_save_step1(): void
    {
        $response = $this->actingAs($this->owner)->post(route('admin.onboarding.saveStep1'), [
            'companyName' => 'Test Pharmacy',
            'phoneNumber' => '+201234567890',
            'address' => 'Cairo, Egypt',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('businesses', [
            'id' => $this->business->id,
            'companyName' => 'Test Pharmacy',
        ]);
    }

    public function test_onboarding_save_step2(): void
    {
        $response = $this->actingAs($this->owner)->post(route('admin.onboarding.saveStep2'), [
            'categoryName' => 'Antibiotics',
            'action' => 'continue',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'business_id' => $this->business->id,
            'categoryName' => 'Antibiotics',
        ]);
    }

    public function test_onboarding_save_step3(): void
    {
        $category = Category::factory()->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->owner)->post(route('admin.onboarding.saveStep3'), [
            'productName' => 'Paracetamol 500mg',
            'category_id' => $category->id,
            'sales_price' => 15.00,
            'purchase_without_tax' => 10.00,
            'action' => 'continue',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'business_id' => $this->business->id,
            'productName' => 'Paracetamol 500mg',
        ]);
    }

    public function test_onboarding_complete(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.onboarding.complete'));
        $response->assertRedirect(route('admin.dashboard.index'));
    }

    public function test_onboarding_skip(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.onboarding.skip'));
        $response->assertRedirect(route('admin.dashboard.index'));
    }

    public function test_onboarding_invalid_step_returns_404(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.onboarding.step', 5));
        $response->assertStatus(404);
    }

    // ─── Getting Started Checklist ─────────────────────────

    public function test_dashboard_shows_checklist(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.dashboard.index'));
        $response->assertStatus(200);
        $response->assertSee('Getting Started');
        $response->assertSee('Business Setup');
        $response->assertSee('Add Categories');
        $response->assertSee('Add Products');
    }

    public function test_dashboard_shows_role_based_content_for_staff(): void
    {
        $response = $this->actingAs($this->staff)->get(route('admin.dashboard.index'));
        $response->assertStatus(200);
        $response->assertSee("Today's Sales");
        $response->assertSee('Low Stock Items');
        $response->assertSee('Quick Actions');
    }

    // ─── Flash Messages ────────────────────────────────────

    public function test_flash_messages_partial_exists(): void
    {
        $this->assertFileExists(resource_path('views/layouts/partials/flash-messages.blade.php'));
    }

    // ─── Dark Mode ─────────────────────────────────────────

    public function test_dark_mode_toggle_route_exists(): void
    {
        $response = $this->actingAs($this->owner)->post(route('toggle-dark-mode'));
        $response->assertStatus(200);
    }

    // ─── Empty States ──────────────────────────────────────

    public function test_empty_state_component_exists(): void
    {
        $this->assertFileExists(resource_path('views/components/empty-state.blade.php'));
    }
}
