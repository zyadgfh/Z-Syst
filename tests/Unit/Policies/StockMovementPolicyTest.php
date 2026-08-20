<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\User;
use App\Policies\StockMovementPolicy;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected StockMovementPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected User $adminUser;
    protected StockMovement $stockMovement;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new StockMovementPolicy();
        
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        
        $this->user = User::factory()->create(['business_id' => $business1->id, 'role' => 'user']);
        $this->otherUser = User::factory()->create(['business_id' => $business2->id, 'role' => 'user']);
        $this->adminUser = User::factory()->create(['business_id' => $business1->id, 'role' => 'admin']);
        $this->stockMovement = StockMovement::factory()->create(['business_id' => $business1->id]);
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
        $this->assertTrue($this->policy->view($this->user, $this->stockMovement));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->stockMovement));
    }

    public function test_create_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_always_denied(): void
    {
        $this->assertFalse($this->policy->update($this->user, $this->stockMovement));
        $this->assertFalse($this->policy->update($this->adminUser, $this->stockMovement));
    }

    public function test_delete_always_denied(): void
    {
        $this->assertFalse($this->policy->delete($this->user, $this->stockMovement));
        $this->assertFalse($this->policy->delete($this->adminUser, $this->stockMovement));
    }

    public function test_restore_allows_admin(): void
    {
        $this->assertTrue($this->policy->restore($this->adminUser, $this->stockMovement));
    }

    public function test_restore_denies_regular_user(): void
    {
        $this->assertFalse($this->policy->restore($this->user, $this->stockMovement));
    }

    public function test_force_delete_always_denied(): void
    {
        $this->assertFalse($this->policy->forceDelete($this->user, $this->stockMovement));
        $this->assertFalse($this->policy->forceDelete($this->adminUser, $this->stockMovement));
    }
}
