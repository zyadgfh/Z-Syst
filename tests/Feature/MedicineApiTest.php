<?php

namespace Tests\Feature;

use Tests\TestCase;

class MedicineApiTest extends TestCase
{

    public function test_can_create_medicine(): void
    {
        $response = $this->postJson('/api/v1/medicines', [
            'name' => 'Paracetamol',
            'sale_price' => 12.5,
            'purchase_price' => 8.0,
            'stock' => 50,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('name', 'Paracetamol');
    }
}
