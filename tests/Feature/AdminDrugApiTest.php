<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\TenantManager;
use Database\Seeders\DemoDrugSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDrugApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_drug_under_tenant(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $this->seed(DemoDrugSeeder::class);

        // create and authenticate a user belonging to the company
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user, 'sanctum');

        // bind tenant for the request context
        app(TenantManager::class)->bindTenant($company);

        $payload = ['name' => 'New Admin Drug', 'generic_name' => 'New Drug'];
        $resp = $this->postJson('/api/v1/admin/drugs', $payload);
        $resp->assertStatus(201)->assertJsonFragment(['name' => 'New Admin Drug']);

        $this->assertDatabaseHas('drugs', ['name' => 'New Admin Drug', 'company_id' => $company->id]);
    }
}
