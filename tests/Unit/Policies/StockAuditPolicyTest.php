<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\StockAudit;
use App\Models\User;
use App\Policies\StockAuditPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAuditPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected StockAuditPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected User $adminUser;
    protected StockAudit $stockAudit;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new StockAuditPolicy();
        
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        
        $this->user = User::factory()->create(['business_id' => $business1->id]);
        $this->otherUser = User::factory()->create(['business_id' => $business2->id]);
        $this->adminUser = User::factory()->create(['business_id' => $business1->id, 'role' => 'admin']);
        $this->stockAudit = StockAudit::factory()->create(['business_id' => $business1->id, 'status' => 'pending']);
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
        $this->assertTrue($this->policy->view($this->user, $this->stockAudit));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->stockAudit));
    }

    public function test_create_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_allows_when_not_completed(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->stockAudit));
    }

    public function test_update_denies_when_completed(): void
    {
        $completedAudit = StockAudit::factory()->create(['business_id' => $this->user->business_id, 'status' => 'completed']);
        $this->assertFalse($this->policy->update($this->user, $completedAudit));
    }

    public function test_delete_allows_when_pending(): void
    {
        $this->assertTrue($this->policy->delete($this->user, $this->stockAudit));
    }

    public function test_start_allows_when_pending(): void
    {
        $this->assertTrue($this->policy->start($this->user, $this->stockAudit));
    }

    public function test_complete_allows_when_in_progress(): void
    {
        $inProgressAudit = StockAudit::factory()->create(['business_id' => $this->user->business_id, 'status' => 'in_progress']);
        $this->assertTrue($this->policy->complete($this->user, $inProgressAudit));
    }

    public function test_cancel_allows_when_pending_or_in_progress(): void
    {
        $this->assertTrue($this->policy->cancel($this->user, $this->stockAudit));
        
        $inProgressAudit = StockAudit::factory()->create(['business_id' => $this->user->business_id, 'status' => 'in_progress']);
        $this->assertTrue($this->policy->cancel($this->user, $inProgressAudit));
    }

    public function test_cancel_denies_when_completed(): void
    {
        $completedAudit = StockAudit::factory()->create(['business_id' => $this->user->business_id, 'status' => 'completed']);
        $this->assertFalse($this->policy->cancel($this->user, $completedAudit));
    }
}
