<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create([
            'business_category_id' => $category->id,
        ]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'shop-owner',
            'lang' => 'ar',
        ]);

        // Seed permissions and role
        $perms = ['dashboard-read', 'products-view', 'settings-view', 'settings-edit'];
        foreach ($perms as $p) {
            \Spatie\Permission\Models\Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $role = \Spatie\Permission\Models\Role::create(['name' => 'shop-owner', 'guard_name' => 'web']);
        $role->syncPermissions($perms);
        $this->user->assignRole('shop-owner');
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_default_locale_is_arabic(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.dashboard.index'));
        $response->assertStatus(200);
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_switching_to_english_via_query_param(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.dashboard.index', ['lang' => 'en']));
        $response->assertStatus(200);
        $this->assertEquals('en', app()->getLocale());

        // Verify it was persisted to user DB
        $this->user->refresh();
        $this->assertEquals('en', $this->user->lang);
    }

    public function test_switching_to_arabic_via_query_param(): void
    {
        $this->user->update(['lang' => 'en']);

        $response = $this->actingAs($this->user)->get(route('admin.dashboard.index', ['lang' => 'ar']));
        $response->assertStatus(200);
        $this->assertEquals('ar', app()->getLocale());

        $this->user->refresh();
        $this->assertEquals('ar', $this->user->lang);
    }

    public function test_user_db_preference_is_used_as_fallback(): void
    {
        $this->user->update(['lang' => 'en']);

        // Visit without ?lang param — should use user's DB preference
        $response = $this->actingAs($this->user)->get(route('admin.dashboard.index'));
        $response->assertStatus(200);
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_session_overrides_user_db_preference(): void
    {
        $this->user->update(['lang' => 'en']);

        // First visit sets session to Arabic
        $this->actingAs($this->user)->get(route('admin.dashboard.index', ['lang' => 'ar']));
        $this->assertEquals('ar', app()->getLocale());

        // Second visit without param should use session (ar), not user DB (en)
        $response = $this->actingAs($this->user)->get(route('admin.dashboard.index'));
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_english_translations_render_in_dashboard(): void
    {
        $this->user->update(['lang' => 'en']);

        $response = $this->actingAs($this->user)->get(route('admin.dashboard.index', ['lang' => 'en']));
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_arabic_translations_render_in_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.dashboard.index'));
        $response->assertStatus(200);
        $response->assertSee(__('dashboard.Dashboard'));
    }

    public function test_html_dir_attribute_changes_with_locale(): void
    {
        // Arabic should be RTL
        $response = $this->actingAs($this->user)->get(route('admin.dashboard.index'));
        $response->assertSee('dir="rtl"', false);

        // English should not be RTL
        $response = $this->actingAs($this->user)->get(route('admin.dashboard.index', ['lang' => 'en']));
        $response->assertDontSee('dir="rtl"');
    }
}
