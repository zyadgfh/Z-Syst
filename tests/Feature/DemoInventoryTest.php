<?php

namespace Tests\Feature;

use App\Models\Company;
use Database\Seeders\DemoDrugSeeder;
use Database\Seeders\DemoInventorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_seed_creates_prices_and_stocks_when_tables_exist(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $this->seed(DemoDrugSeeder::class);
        $this->seed(DemoInventorySeeder::class);

        if (Schema::hasTable('drug_prices')) {
            $count = DB::table('drug_prices')->where('company_id', $company->id)->count();
            $this->assertGreaterThan(0, $count, 'Expected drug_prices to be seeded');
        } else {
            $this->markTestSkipped('Table drug_prices does not exist in this schema');
        }

        if (Schema::hasTable('product_stocks')) {
            $count = DB::table('product_stocks')->where('company_id', $company->id)->count();
            $this->assertGreaterThan(0, $count, 'Expected product_stocks to be seeded');
        } else {
            $this->markTestSkipped('Table product_stocks does not exist in this schema');
        }
    }
}
