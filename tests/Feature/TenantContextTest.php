<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

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

        $resolver = new TenantResolver;
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
            'role' => 'superadmin',
        ]);

        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(function () use ($superAdmin) {
            return $superAdmin;
        });

        $resolver = new TenantResolver;
        $tenantId = $resolver->resolve($request);

        $this->assertNull($tenantId);
    }

    /**
     * Test tenant resolver accepts business_id parameter
     */
    public function test_tenant_resolver_accepts_business_id_parameter()
    {
        $business = Business::factory()->create();

        $request = Request::create('/api/v1/test?business_id='.$business->id, 'GET');

        $resolver = new TenantResolver;
        $tenantId = $resolver->resolve($request);

        $this->assertEquals($business->id, $tenantId);
    }

    /**
     * Test tenant access check blocks cross-tenant access
     */
    public function test_tenant_access_check_blocks_cross_tenant_access()
    {
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business1->id]);

        $resolver = new TenantResolver;
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

        $this->actingAs($user);
        $resolver = new TenantResolver;
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

        $this->actingAs($superAdmin);
        $resolver = new TenantResolver;
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

        // Authenticate as user from business1
        $actingUser = User::factory()->create(['business_id' => $business1->id, 'role' => 'staff']);
        $this->actingAs($actingUser);

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

        // Authenticate as superadmin
        $actingSuperAdmin = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($actingSuperAdmin);

        $users = User::all();

        // Should return all users since superadmin is not filtered
        $this->assertCount(2, $users);
    }
}
