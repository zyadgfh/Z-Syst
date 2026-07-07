<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\TenantManager;
use Database\Seeders\DemoDrugSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDrugListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_search_drugs(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $this->seed(DemoDrugSeeder::class);

        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user, 'sanctum');
        app(TenantManager::class)->bindTenant($company);

        $resp = $this->getJson('/api/v1/admin/drugs');
        $resp->assertStatus(200)->assertJsonStructure(['data']);

        $first = $resp->json('data.0');
        $this->assertNotEmpty($first['name']);

        // search
        $q = substr($first['name'], 0, 4);
        $search = $this->getJson('/api/v1/admin/drugs?q=' . urlencode($q));
        $search->assertStatus(200)->assertJsonStructure(['data']);
    }
}
