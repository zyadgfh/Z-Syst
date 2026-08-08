<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\CustomerInteraction;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTransaction;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Party $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->customer = Party::factory()->create([
            'business_id' => $this->business->id,
            'type' => 'customer',
        ]);
    }

    public function test_can_list_loyalty_programs(): void
    {
        LoyaltyProgram::create([
            'business_id' => $this->business->id,
            'name' => 'Standard Rewards',
            'points_per_currency' => 1,
            'min_points_for_reward' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/loyalty');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'points_per_currency', 'min_points_for_reward', 'is_active'],
                ],
            ]);
    }

    public function test_can_create_loyalty_program(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/loyalty', [
                'name' => 'Premium Rewards',
                'points_per_currency' => 2,
                'min_points_for_reward' => 200,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Premium Rewards')
            ->assertJsonPath('data.points_per_currency', 2);

        $this->assertDatabaseHas('loyalty_programs', [
            'business_id' => $this->business->id,
            'name' => 'Premium Rewards',
        ]);
    }

    public function test_can_update_loyalty_program(): void
    {
        $program = LoyaltyProgram::create([
            'business_id' => $this->business->id,
            'name' => 'Old Program',
            'points_per_currency' => 1,
            'min_points_for_reward' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/loyalty/{$program->id}", [
                'name' => 'Updated Program',
                'points_per_currency' => 3,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Program')
            ->assertJsonPath('data.points_per_currency', 3);
    }

    public function test_can_delete_loyalty_program(): void
    {
        $program = LoyaltyProgram::create([
            'business_id' => $this->business->id,
            'name' => 'To Delete',
            'points_per_currency' => 1,
            'min_points_for_reward' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/loyalty/{$program->id}");

        $response->assertOk()
            ->assertJsonFragment(['message' => __('Loyalty program deleted successfully.')]);

        $this->assertDatabaseMissing('loyalty_programs', ['id' => $program->id]);
    }

    public function test_can_get_customer_balance(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/loyalty/balance/{$this->customer->id}");

        $response->assertOk()
            ->assertJsonPath('data.party_id', $this->customer->id)
            ->assertJsonPath('data.balance', 0);
    }

    public function test_can_get_customer_history(): void
    {
        $program = LoyaltyProgram::create([
            'business_id' => $this->business->id,
            'name' => 'Standard',
            'points_per_currency' => 1,
            'min_points_for_reward' => 100,
            'is_active' => true,
        ]);

        LoyaltyTransaction::create([
            'business_id' => $this->business->id,
            'loyalty_program_id' => $program->id,
            'party_id' => $this->customer->id,
            'points' => 100,
            'type' => 'earned',
            'notes' => 'Points earned',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/loyalty/history/{$this->customer->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'points', 'type'],
                ],
            ]);
    }

    public function test_can_view_customer_interactions(): void
    {
        CustomerInteraction::create([
            'business_id' => $this->business->id,
            'party_id' => $this->customer->id,
            'type' => 'call',
            'notes' => 'Follow-up on medication',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/loyalty/interactions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'party_id', 'type', 'notes'],
                ],
            ]);
    }

    public function test_cannot_access_other_business_program(): void
    {
        $otherBusiness = Business::factory()->create();
        $program = LoyaltyProgram::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other',
            'points_per_currency' => 1,
            'min_points_for_reward' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/loyalty/{$program->id}");

        $response->assertForbidden();
    }
}
