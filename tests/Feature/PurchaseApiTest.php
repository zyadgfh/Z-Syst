<?php

namespace Tests\Feature;

use Tests\TestCase;

class PurchaseApiTest extends TestCase
{
    public function test_can_create_purchase_order(): void
    {
        $response = $this->postJson('/api/v1/purchases', [
            'supplier_id' => 1,
            'status' => 'pending',
            'total_amount' => 500,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'pending');
    }
}
