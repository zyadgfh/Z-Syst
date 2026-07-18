<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    public function test_can_create_customer(): void
    {
        $response = $this->postJson('/api/v1/customers', [
            'name' => 'Ahmed Ali',
            'phone' => '01111111111',
            'email' => 'ahmed@example.com',
            'address' => 'Giza',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('name', 'Ahmed Ali');
    }
}
