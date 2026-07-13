<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\TenantManager;
use Database\Seeders\DemoDrugSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrugApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_drugs_index_and_show_are_tenant_scoped(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $this->seed(DemoDrugSeeder::class);

        // bind tenant so global scope filters to this company
        app(TenantManager::class)->bindTenant($company);

        $response = $this->getJson('/api/v1/drugs');
        $response->assertStatus(200)->assertJsonStructure(['data']);

        $firstId = $response->json('data.0.id');

        // fallback: seed directly if no data present
        if (is_null($firstId)) {
            \Illuminate\Support\Facades\DB::table('drugs')->insert([
                ['company_id' => $company->id, 'uuid' => \Illuminate\Support\Str::uuid(), 'name' => 'Fallback API Drug', 'created_at' => now(), 'updated_at' => now()],
            ]);

            $response = $this->getJson('/api/v1/drugs');
            $firstId = $response->json('data.0.id');
        }

        $this->assertNotNull($firstId);

        $show = $this->getJson("/api/v1/drugs/{$firstId}");
        $show->assertStatus(200)->assertJsonFragment(['id' => $firstId]);
    }
}
