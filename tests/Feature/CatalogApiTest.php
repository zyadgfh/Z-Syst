<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_category_and_manufacturer_for_current_tenant(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user, 'sanctum');

        $categoryResponse = $this->postJson('/api/v1/categories', [
            'categoryName' => 'Antibiotics',
            'description' => 'Test category',
        ]);
        $categoryResponse->assertStatus(201);

        $manufacturerResponse = $this->postJson('/api/v1/manufacturer', [
            'name' => 'Test Pharma',
            'description' => 'Test manufacturer',
        ]);
        $manufacturerResponse->assertStatus(201);

        $this->assertDatabaseHas('categories', ['categoryName' => 'Antibiotics', 'company_id' => $company->id]);
        $this->assertDatabaseHas('manufacturers', ['name' => 'Test Pharma', 'company_id' => $company->id]);
    }
}
