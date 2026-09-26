<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_resolver_resolves_regular_user_tenant(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);

        $request = Request::create('/api/v1/products', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->assertSame($business->id, app(TenantResolver::class)->resolve($request));
    }

    public function test_tenant_resolver_rejects_requested_tenant_for_regular_user(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);

        $request = Request::create('/api/v1/products', 'GET', ['business_id' => $otherBusiness->id]);
        $request->setUserResolver(fn () => $user);

        $this->assertSame($business->id, app(TenantResolver::class)->resolve($request));
        $this->assertFalse(app(TenantResolver::class)->canAccessTenant($otherBusiness->id));
    }

    public function test_superadmin_can_select_an_explicit_tenant(): void
    {
        $business = Business::factory()->create();
        $superAdmin = User::factory()->create(['role' => 'superadmin']);

        $request = Request::create('/api/v1/products', 'GET', ['business_id' => $business->id]);
        $request->setUserResolver(fn () => $superAdmin);

        $this->assertSame($business->id, app(TenantResolver::class)->resolve($request));
        $this->assertTrue(app(TenantResolver::class)->canAccessTenant($business->id));
    }

    public function test_regular_user_without_tenant_fails_closed_in_tenant_access_middleware(): void
    {
        $user = User::factory()->create(['business_id' => null]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/products')
            ->assertForbidden();
    }

    public function test_business_models_are_globally_scoped_by_active_tenant_context(): void
    {
        app()->forgetInstance('tenant_id');

        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        Product::factory()->create(['business_id' => $businessA->id]);
        Product::factory()->create(['business_id' => $businessB->id]);

        app()->instance('tenant_id', $businessA->id);

        $products = Product::query()->get();

        $this->assertCount(1, $products);
        $this->assertSame($businessA->id, (int) $products->first()->business_id);
    }

    public function test_cross_tenant_product_cannot_be_loaded_through_api(): void
    {
        app()->forgetInstance('tenant_id');

        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        $userA = User::factory()->create(['business_id' => $businessA->id]);
        $productB = Product::factory()->create(['business_id' => $businessB->id]);

        $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/products/{$productB->id}")
            ->assertNotFound();
    }

    public function test_model_cannot_be_created_for_another_tenant_when_context_is_active(): void
    {
        app()->instance('tenant_id', 12345);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Product::factory()->create(['business_id' => 12346]);
    }
}
