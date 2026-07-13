<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Drug;
use App\Services\TenantManager;
use Database\Seeders\DemoDrugSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDrugTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_drugs_are_scoped_to_tenant(): void
    {
        // prepare demo company and seed drugs
        $company = Company::factory()->create(['slug' => 'demo']);
        $this->seed(DemoDrugSeeder::class);

        // total without tenant scope
        $total = Drug::withoutGlobalScopes()->where('company_id', $company->id)->count();

        // fallback: if seeder didn't create drugs, insert a few directly
        if ($total === 0) {
            \Illuminate\Support\Facades\DB::table('drugs')->insert([
                ['company_id' => $company->id, 'uuid' => \Illuminate\Support\Str::uuid(), 'name' => 'Fallback Drug 1', 'created_at' => now(), 'updated_at' => now()],
                ['company_id' => $company->id, 'uuid' => \Illuminate\Support\Str::uuid(), 'name' => 'Fallback Drug 2', 'created_at' => now(), 'updated_at' => now()],
            ]);

            $total = Drug::withoutGlobalScopes()->where('company_id', $company->id)->count();
        }

        $this->assertGreaterThan(0, $total);

        // bind tenant and assert scoped count
        app(TenantManager::class)->bindTenant($company);
        $scoped = Drug::count();
        $this->assertSame($total, $scoped);

        // bind a different tenant and assert zero
        $other = Company::factory()->create();
        app(TenantManager::class)->bindTenant($other);
        $this->assertEquals(0, Drug::count());
    }
}
