<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ZSystExtendedApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--path' => 'Modules/ZSyst/Database/migrations', '--force' => true]);
    }

    public function test_supplier_customer_and_sales_endpoints_work(): void
    {
        $supplierResponse = $this->postJson('/api/zsyst/suppliers', [
            'name' => 'Medi Supply Co',
            'contact_person' => 'Ali',
            'phone' => '0500000000',
            'email' => 'ali@medisupply.test',
            'address' => 'Dubai',
        ]);

        $supplierResponse->assertCreated();
        $supplierResponse->assertJsonFragment(['name' => 'Medi Supply Co']);

        $customerResponse = $this->postJson('/api/zsyst/customers', [
            'name' => 'Sara Patient',
            'phone' => '0555555555',
            'email' => 'sara@example.test',
            'loyalty_points' => 120,
        ]);

        $customerResponse->assertCreated();
        $customerResponse->assertJsonFragment(['name' => 'Sara Patient']);

        $saleResponse = $this->postJson('/api/zsyst/pos/sales', [
            'customer_name' => 'Sara Patient',
            'status' => 'completed',
            'subtotal' => 150.00,
            'tax_amount' => 15.00,
            'total_amount' => 165.00,
            'payment_method' => 'cash',
            'notes' => 'Walk-in sale',
        ]);

        $saleResponse->assertCreated();
        $saleResponse->assertJsonFragment(['status' => 'completed']);

        $this->assertDatabaseHas('suppliers', ['name' => 'Medi Supply Co']);
        $this->assertDatabaseHas('customers', ['name' => 'Sara Patient']);
        $this->assertDatabaseHas('pos_sales', ['customer_name' => 'Sara Patient', 'total_amount' => '165.00']);
    }
}
