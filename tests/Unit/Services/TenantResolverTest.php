<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\TenantResolver;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantResolverTest extends TestCase
{
    public function test_unauthenticated_requests_cannot_select_a_tenant(): void
    {
        $request = Request::create('/api/v1/products', 'GET', ['business_id' => 99]);

        $this->assertNull(app(TenantResolver::class)->resolve($request));
    }

    public function test_regular_users_are_bound_to_their_own_tenant(): void
    {
        $user = new User();
        $user->business_id = 12;
        $user->role = 'user';

        $request = Request::create('/api/v1/products', 'GET', ['business_id' => 99]);
        $request->setUserResolver(fn () => $user);

        $this->assertSame(12, app(TenantResolver::class)->resolve($request));
    }

    public function test_superadmin_can_explicitly_select_a_tenant(): void
    {
        $user = new User();
        $user->role = 'superadmin';

        $request = Request::create('/api/v1/products', 'GET', ['business_id' => 99]);
        $request->setUserResolver(fn () => $user);

        $this->assertSame(99, app(TenantResolver::class)->resolve($request));
    }
}
