<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_json_creates_products(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user, 'sanctum');

        $rows = [
            ['name' => 'Imported A', 'generic_name' => 'Imp A'],
            ['name' => 'Imported B', 'generic_name' => 'Imp B', 'barcode' => 'imp-b'],
        ];

        $resp = $this->postJson('/api/v1/admin/import/products/json', ['rows' => $rows]);
        $resp->assertStatus(200)->assertJsonStructure(['created']);
        $this->assertDatabaseHas('drugs', ['name' => 'Imported A']);
        $this->assertDatabaseHas('drugs', ['barcode' => 'imp-b']);
    }
}
