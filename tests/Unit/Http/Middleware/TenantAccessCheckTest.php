<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\TenantAccessCheck;
use App\Models\Product;
use App\Models\User;
use App\Services\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TenantAccessCheckTest extends TestCase
{
    private function requestFor(User $user, array $input = [], array $routeParameters = []): Request
    {
        $request = Request::create('/api/v1/test', 'GET', $input);
        $request->setUserResolver(fn () => $user);

        $route = new Route(['GET'], '/api/v1/test', fn () => null);
        $route->setParameters($routeParameters);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    public function test_regular_user_cannot_select_another_business(): void
    {
        $user = new User();
        $user->business_id = 10;
        $user->role = 'user';

        $request = $this->requestFor($user, ['business_id' => 20]);

        $this->expectException(HttpException::class);
        app(TenantAccessCheck::class)->handle($request, fn ($request) => response()->noContent());
    }

    public function test_regular_user_cannot_access_a_bound_model_from_another_business(): void
    {
        $user = new User();
        $user->business_id = 10;
        $user->role = 'user';

        $product = new Product();
        $product->business_id = 20;

        $request = $this->requestFor($user, [], ['product' => $product]);

        $this->expectException(HttpException::class);
        app(TenantAccessCheck::class)->handle($request, fn ($request) => response()->noContent());
    }

    public function test_regular_user_can_access_a_bound_model_from_their_business(): void
    {
        $user = new User();
        $user->business_id = 10;
        $user->role = 'user';

        $product = new Product();
        $product->business_id = 10;

        $response = app(TenantAccessCheck::class)->handle(
            $this->requestFor($user, [], ['product' => $product]),
            fn ($request) => response()->noContent()
        );

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_superadmin_can_cross_tenant_boundaries(): void
    {
        $user = new User();
        $user->business_id = 1;
        $user->role = 'superadmin';

        $product = new Product();
        $product->business_id = 20;

        $response = app(TenantAccessCheck::class)->handle(
            $this->requestFor($user, [], ['product' => $product]),
            fn ($request) => response()->noContent()
        );

        $this->assertSame(204, $response->getStatusCode());
    }
}
