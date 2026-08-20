<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\GRN;
use App\Models\User;
use App\Policies\GRNPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GRNPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected GRNPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected GRN $grn;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new GRNPolicy();
        
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        
        $this->user = User::factory()->create(['business_id' => $business1->id]);
        $this->otherUser = User::factory()->create(['business_id' => $business2->id]);
        $this->grn = GRN::factory()->create(['business_id' => $business1->id, 'status' => 'pending']);
    }

    public function test_view_any_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_view_any_denies_users_without_business(): void
    {
        $userWithoutBusiness = User::factory()->create(['business_id' => null]);
        $this->assertFalse($this->policy->viewAny($userWithoutBusiness));
    }

    public function test_view_allows_owner(): void
    {
        $this->assertTrue($this->policy->view($this->user, $this->grn));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->grn));
    }

    public function test_create_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_allows_when_pending(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->grn));
    }

    public function test_update_denies_when_not_pending(): void
    {
        $verifiedGrn = GRN::factory()->create(['business_id' => $this->user->business_id, 'status' => 'verified']);
        $this->assertFalse($this->policy->update($this->user, $verifiedGrn));
    }

    public function test_update_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->update($this->otherUser, $this->grn));
    }

    public function test_delete_allows_when_pending(): void
    {
        $this->assertTrue($this->policy->delete($this->user, $this->grn));
    }

    public function test_delete_denies_when_not_pending(): void
    {
        $verifiedGrn = GRN::factory()->create(['business_id' => $this->user->business_id, 'status' => 'verified']);
        $this->assertFalse($this->policy->delete($this->user, $verifiedGrn));
    }

    public function test_delete_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->delete($this->otherUser, $this->grn));
    }
}
