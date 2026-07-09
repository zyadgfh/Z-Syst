<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_manager_resolves_company_by_host_subdomain(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);

        $tenantManager = app(TenantManager::class);
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'demo.example.com']);

        $resolvedCompany = $tenantManager->resolveFromRequest($request);

        $this->assertNotNull($resolvedCompany);
        $this->assertSame($company->id, $resolvedCompany->id);
        $this->assertSame($company->id, $tenantManager->getCompanyId());
    }
}
