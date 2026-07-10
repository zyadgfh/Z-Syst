<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ZSystDrugApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--path' => 'Modules/ZSyst/Database/migrations', '--force' => true]);
    }

    public function test_drug_directory_endpoint_returns_data(): void
    {
        $response = $this->getJson('/api/zsyst/drugs');

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_legacy_z_syst_prefix_still_serves_drugs_endpoint(): void
    {
        $response = $this->getJson('/api/z-syst/drugs');

        $response->assertOk();
        $response->assertJson([]);
    }
}
