<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\User;
use App\Policies\PurchaseReturnPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseReturnPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected Business $business;
    protected PurchaseReturn $return;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PurchaseReturnPolicy();

        $this->business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

        $purchase = Purchase::factory()->create(['business_id' => $this->business->id]);

        $this->return = PurchaseReturn::create([
            'business_id' => $this->business->id,
            'purchase_id' => $purchase->id,
            'invoice_no' => 'PR-00001',
            'return_date' => now(),
            'status' => 'pending',
        ]);
    }

    public function test_view_any_allows_user_with_business(): void
    {
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_view_any_denies_user_without_business(): void
    {
        $noBusiness = User::factory()->create(['business_id' => null]);
        $this->assertFalse($this->policy->viewAny($noBusiness));
    }

    public function test_view_allows_same_business(): void
    {
        $this->assertTrue($this->policy->view($this->user, $this->return));
    }

    public function test_view_denies_other_business(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->return));
    }

    public function test_create_allows_user_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_create_denies_user_without_business(): void
    {
        $noBusiness = User::factory()->create(['business_id' => null]);
        $this->assertFalse($this->policy->create($noBusiness));
    }

    public function test_update_allows_same_business(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->return));
    }

    public function test_update_denies_other_business(): void
    {
        $this->assertFalse($this->policy->update($this->otherUser, $this->return));
    }

    public function test_delete_allows_same_business(): void
    {
        $this->assertTrue($this->policy->delete($this->user, $this->return));
    }

    public function test_delete_denies_other_business(): void
    {
        $this->assertFalse($this->policy->delete($this->otherUser, $this->return));
    }
}
