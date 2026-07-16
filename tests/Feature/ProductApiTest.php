<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Drug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_search_product_for_current_tenant(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/admin/drugs', [
            'name' => 'Paracetamol',
            'generic_name' => 'Acetaminophen',
            'barcode' => 'PARA-001',
        ]);

        $response->assertStatus(201);

        $searchResponse = $this->getJson('/api/v1/admin/drugs?q=Paracetamol');
        $searchResponse->assertStatus(200);
        $this->assertDatabaseHas('drugs', ['barcode' => 'PARA-001', 'company_id' => $company->id]);
    }
}
