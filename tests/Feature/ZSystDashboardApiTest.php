<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ZSystDashboardApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--path' => 'Modules/ZSyst/Database/migrations', '--force' => true]);
    }

    public function test_dashboard_returns_summary_payload(): void
    {
        $this->postJson('/api/zsyst/drugs', [
            'name' => 'Paracetamol',
            'barcode' => 'PARA001',
            'sale_price' => 10.00,
            'purchase_price' => 7.50,
            'is_active' => true,
        ]);

        $response = $this->getJson('/zsyst');

        $response->assertOk();
        $response->assertJsonFragment(['module' => 'ZSyst']);
        $response->assertJsonPath('summary.drugs', 1);
    }
}
