<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ZSystLoyaltyApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--path' => 'Modules/ZSyst/Database/migrations', '--force' => true]);
    }

    public function test_loyalty_transactions_can_be_created_and_tracked(): void
    {
        $customerResponse = $this->postJson('/api/zsyst/customers', [
            'name' => 'Loyal Customer',
            'phone' => '0550000000',
            'email' => 'loyal@example.test',
            'loyalty_points' => 10,
        ]);

        $customerResponse->assertCreated();

        $response = $this->postJson('/api/zsyst/loyalty/transactions', [
            'customer_id' => 1,
            'type' => 'earn',
            'points' => 25,
            'description' => 'Purchase bonus',
        ]);

        $response->assertCreated();
        $response->assertJsonFragment(['message' => 'Loyalty transaction recorded']);

        $this->assertDatabaseHas('loyalty_point_transactions', ['customer_id' => 1, 'points' => 25]);
    }
}
