<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyApiTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    private $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_parties()
    {
        $response = $this->getJson('/api/v1/parties');
        $response->assertStatus(401);
    }

    public function test_can_list_parties()
    {
        Party::factory(3)->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/parties');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => ['id', 'name', 'phone', 'email', 'type', 'address', 'due', 'opening_balance', 'status'],
                ],
            ]);
    }

    public function test_can_create_party()
    {
        $partyData = [
            'name' => 'Test Supplier',
            'phone' => '01234567890',
            'email' => 'supplier@test.com',
            'type' => 'supplier',
            'address' => '123 Test St',
            'due' => 0,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/parties', $partyData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => __('Data saved successfully.'),
            ]);

        // Phone is encrypted in DB, so verify via model
        $party = Party::where('name', 'Test Supplier')
            ->where('business_id', $this->business->id)
            ->first();
        $this->assertNotNull($party);
        $this->assertEquals('01234567890', $party->phone);
    }

    public function test_cannot_create_party_with_duplicate_phone()
    {
        Party::factory()->create([
            'phone' => '01234567890',
            'business_id' => $this->business->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/parties', [
                'name' => 'Another Supplier',
                'phone' => '01234567890',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_update_party()
    {
        $party = Party::factory()->create([
            'business_id' => $this->business->id,
            'opening_balance' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/parties/{$party->id}", [
                'name' => 'Updated Name',
                'phone' => $party->phone,
                'type' => $party->party_type ?? 'customer',
                'due' => 100,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_party()
    {
        $party = Party::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/parties/{$party->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => __('Data deleted successfully.'),
            ]);

        $this->assertDatabaseMissing('parties', ['id' => $party->id]);
    }
}
