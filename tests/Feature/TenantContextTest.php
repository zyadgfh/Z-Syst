<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test tenant resolver resolves from authenticated user
     */
    public function test_tenant_resolver_resolves_from_authenticated_user()
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);

        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $resolver = new TenantResolver();
        $tenantId = $resolver->resolve($request);

        $this->assertEquals($business->id, $tenantId);
    }

    /**
     * Test tenant resolver skips superadmin
     */
    public function test_tenant_resolver_skips_superadmin()
    {
        $business = Business::factory()->create();
        $superAdmin = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'superadmin'
        ]);

        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(function () use ($superAdmin) {
            return $superAdmin;
        });

        $resolver = new TenantResolver();
        $tenantId = $resolver->resolve($request);

        $this->assertNull($tenantId);
    }

    /**
     * Tenant identity must NEVER come from client input: a business_id
     * parameter without an authenticated principal resolves to no context.
     * (This test previously asserted the opposite — it blessed the hole.)
     */
    public function test_tenant_resolver_rejects_client_supplied_business_id()
    {
        $business = Business::factory()->create();

        $request = Request::create('/api/v1/test?business_id=' . $business->id, 'GET');

        $resolver = new TenantResolver();
        $tenantId = $resolver->resolve($request);

        $this->assertNull($tenantId);
    }

    /**
     * trustedBusinessId(): a tenant user's client-supplied business_id is
     * ignored — the verified identity wins.
     */
    public function test_trusted_business_id_ignores_client_input_for_tenant_users()
    {
        $own = Business::factory()->create();
        $foreign = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $own->id, 'role' => 'admin']);

        $request = Request::create('/admin/audit-logs?business_id=' . $foreign->id, 'GET');
        $request->setUserResolver(fn () => $user);

        $resolver = new TenantResolver();
        $this->assertSame($own->id, $resolver->trustedBusinessId($request));
    }

    /**
     * trustedBusinessId(): superadmin keeps the reviewed exception of scoping
     * explicitly via business_id (and falls back to their own context).
     */
    public function test_trusted_business_id_honors_explicit_superadmin_scope()
    {
        $target = Business::factory()->create();
        $superAdmin = User::factory()->create(['role' => 'superadmin', 'business_id' => null]);

        $request = Request::create('/admin/audit-logs?business_id=' . $target->id, 'GET');
        $request->setUserResolver(fn () => $superAdmin);

        $resolver = new TenantResolver();
        $this->assertSame($target->id, $resolver->trustedBusinessId($request));
    }

    /**
     * No authenticated principal → no tenant access (deny by default).
     */
    public function test_tenant_access_check_denies_without_authenticated_user()
    {
        $business = Business::factory()->create();

        $resolver = new TenantResolver();
        $this->assertFalse($resolver->canAccessTenant($business->id));
    }

    /**
     * Test tenant access check blocks cross-tenant access
     */
    public function test_tenant_access_check_blocks_cross_tenant_access()
    {
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business1->id]);
        \Illuminate\Support\Facades\Auth::login($user);

        $resolver = new TenantResolver();
        $canAccess = $resolver->canAccessTenant($business2->id);

        $this->assertFalse($canAccess);
    }

    /**
     * Test tenant access check allows same-tenant access
     */
    public function test_tenant_access_check_allows_same_tenant_access()
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        \Illuminate\Support\Facades\Auth::login($user);

        $resolver = new TenantResolver();
        $canAccess = $resolver->canAccessTenant($business->id);

        $this->assertTrue($canAccess);
    }

    /**
     * Test tenant access check allows superadmin
     */
    public function test_tenant_access_check_allows_superadmin()
    {
        $business = Business::factory()->create();
        $superAdmin = User::factory()->create(['role' => 'superadmin']);
        \Illuminate\Support\Facades\Auth::login($superAdmin);

        $resolver = new TenantResolver();
        $canAccess = $resolver->canAccessTenant($business->id);

        $this->assertTrue($canAccess);
    }

    /**
     * Test user model global scope applies tenant filtering
     */
    public function test_user_model_global_scope_applies_tenant_filtering()
    {
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();

        User::factory()->create(['business_id' => $business1->id]);
        User::factory()->create(['business_id' => $business1->id]);
        User::factory()->create(['business_id' => $business2->id]);

        // Simulate authenticated user from business1
        auth()->shouldReceive('check')->andReturn(true);
        auth()->shouldReceive('user')->andReturn(
            User::factory()->make(['business_id' => $business1->id, 'role' => 'staff'])
        );

        $users = User::all();

        // Should only return users from business1
        $this->assertCount(2, $users);
        foreach ($users as $user) {
            $this->assertEquals($business1->id, $user->business_id);
        }
    }

    /**
     * Test user model global scope skips for superadmin
     */
    public function test_user_model_global_scope_skips_for_superadmin()
    {
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();

        User::factory()->create(['business_id' => $business1->id]);
        User::factory()->create(['business_id' => $business2->id]);

        // Simulate superadmin
        auth()->shouldReceive('check')->andReturn(true);
        auth()->shouldReceive('user')->andReturn(
            User::factory()->make(['role' => 'superadmin'])
        );

        $users = User::all();

        // Should return all users since superadmin is not filtered
        $this->assertCount(2, $users);
    }
}
