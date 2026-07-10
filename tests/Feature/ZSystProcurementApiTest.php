<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ZSystProcurementApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--path' => 'Modules/ZSyst/Database/migrations', '--force' => true]);
    }

    public function test_procurement_orders_can_be_created_and_listed(): void
    {
        $response = $this->postJson('/api/zsyst/procurement/orders', [
            'supplier_name' => 'Medi Supply',
            'status' => 'draft',
            'expected_delivery_at' => now()->addDays(3)->toDateString(),
            'notes' => 'Initial order',
            'total_amount' => 1250.50,
        ]);

        $response->assertCreated();
        $response->assertJsonFragment(['supplier_name' => 'Medi Supply']);

        $this->assertDatabaseHas('procurement_orders', [
            'supplier_name' => 'Medi Supply',
            'status' => 'draft',
        ]);

        $listResponse = $this->getJson('/api/zsyst/procurement/orders');

        $listResponse->assertOk();
        $listResponse->assertJsonFragment(['supplier_name' => 'Medi Supply']);
    }
}
