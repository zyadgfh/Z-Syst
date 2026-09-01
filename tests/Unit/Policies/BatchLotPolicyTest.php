<?php

namespace Tests\Unit\Policies;

use App\Models\BatchLot;
use App\Models\Business;
use App\Models\User;
use App\Policies\BatchLotPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchLotPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected BatchLotPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected User $adminUser;
    protected BatchLot $batchLot;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new BatchLotPolicy();
        
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        
        $this->user = User::factory()->create(['business_id' => $business1->id, 'role' => 'user']);
        $this->otherUser = User::factory()->create(['business_id' => $business2->id, 'role' => 'user']);
        $this->adminUser = User::factory()->create(['business_id' => $business1->id, 'role' => 'admin']);
        $this->batchLot = BatchLot::factory()->create(['business_id' => $business1->id]);
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
        $this->assertTrue($this->policy->view($this->user, $this->batchLot));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->batchLot));
    }

    public function test_create_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_allows_owner(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->batchLot));
    }

    public function test_update_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->update($this->otherUser, $this->batchLot));
    }

    public function test_delete_allows_admin(): void
    {
        $this->assertTrue($this->policy->delete($this->adminUser, $this->batchLot));
    }

    public function test_delete_denies_regular_user(): void
    {
        $this->assertFalse($this->policy->delete($this->user, $this->batchLot));
    }

    public function test_recall_allows_admin(): void
    {
        $this->assertTrue($this->policy->recall($this->adminUser, $this->batchLot));
    }

    public function test_recall_denies_regular_user(): void
    {
        $this->assertFalse($this->policy->recall($this->user, $this->batchLot));
    }

    public function test_recall_denies_non_owner_admin(): void
    {
        $otherAdmin = User::factory()->create(['business_id' => $this->otherUser->business_id, 'role' => 'admin']);
        $this->assertFalse($this->policy->recall($otherAdmin, $this->batchLot));
    }
}
