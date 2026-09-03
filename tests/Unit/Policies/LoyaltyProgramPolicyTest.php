<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\LoyaltyProgram;
use App\Models\User;
use App\Policies\LoyaltyProgramPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyProgramPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected LoyaltyProgramPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected LoyaltyProgram $loyaltyProgram;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new LoyaltyProgramPolicy();
        
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        
        $this->user = User::factory()->create(['business_id' => $business1->id]);
        $this->otherUser = User::factory()->create(['business_id' => $business2->id]);
        $this->loyaltyProgram = LoyaltyProgram::factory()->create(['business_id' => $business1->id]);
    }

    public function test_view_any_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_view_allows_owner(): void
    {
        $this->assertTrue($this->policy->view($this->user, $this->loyaltyProgram));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->loyaltyProgram));
    }

    public function test_create_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_allows_owner(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->loyaltyProgram));
    }

    public function test_delete_allows_owner(): void
    {
        $this->assertTrue($this->policy->delete($this->user, $this->loyaltyProgram));
    }

    public function test_delete_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->delete($this->otherUser, $this->loyaltyProgram));
    }
}
