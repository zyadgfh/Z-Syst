<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ZSystInventoryItemApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--path' => 'Modules/ZSyst/Database/migrations', '--force' => true]);
    }

    public function test_inventory_items_can_be_created_and_listed(): void
    {
        $drugResponse = $this->postJson('/api/zsyst/drugs', [
            'name' => 'Amoxicillin',
            'barcode' => '123456',
            'sale_price' => 20.00,
            'purchase_price' => 15.00,
            'is_active' => true,
        ]);

        $drugResponse->assertCreated();

        $response = $this->postJson('/api/zsyst/inventory/items', [
            'drug_id' => 1,
            'batch_number' => 'BATCH-001',
            'expiry_date' => now()->addYear()->toDateString(),
            'quantity_on_hand' => 100,
            'unit_cost' => 14.50,
            'location' => 'A1-01',
        ]);

        $response->assertCreated();
        $response->assertJsonFragment(['message' => 'Inventory item recorded']);

        $this->assertDatabaseHas('inventory_items', ['batch_number' => 'BATCH-001', 'quantity_on_hand' => 100]);
    }
}
