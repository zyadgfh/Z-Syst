<?php

namespace Tests\Feature;

use Tests\TestCase;

class SupplierApiTest extends TestCase
{
    public function test_can_create_supplier(): void
    {
        $response = $this->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/suppliers', [
                'name' => 'ABC Pharma',
                'phone' => '01000000000',
                'email' => 'sales@abc.com',
                'address' => 'Cairo',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('name', 'ABC Pharma');
    }
}
