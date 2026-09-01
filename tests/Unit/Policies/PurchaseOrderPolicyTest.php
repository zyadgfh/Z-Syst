<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Policies\PurchaseOrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseOrderPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected PurchaseOrder $purchaseOrder;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new PurchaseOrderPolicy();
        
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        
        $this->user = User::factory()->create(['business_id' => $business1->id]);
        $this->otherUser = User::factory()->create(['business_id' => $business2->id]);
        $this->purchaseOrder = PurchaseOrder::factory()->create(['business_id' => $business1->id, 'status' => 'draft']);
    }

    public function test_view_any_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_view_allows_owner(): void
    {
        $this->assertTrue($this->policy->view($this->user, $this->purchaseOrder));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->purchaseOrder));
    }

    public function test_create_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_allows_owner(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->purchaseOrder));
    }

    public function test_delete_allows_owner_when_draft(): void
    {
        $this->assertTrue($this->policy->delete($this->user, $this->purchaseOrder));
    }

    public function test_delete_denies_when_not_draft(): void
    {
        $approvedOrder = PurchaseOrder::factory()->create(['business_id' => $this->user->business_id, 'status' => 'approved']);
        $this->assertFalse($this->policy->delete($this->user, $approvedOrder));
    }

    public function test_delete_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->delete($this->otherUser, $this->purchaseOrder));
    }
}
