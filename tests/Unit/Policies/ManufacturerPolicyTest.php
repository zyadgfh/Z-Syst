<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\Manufacturer;
use App\Models\User;
use App\Policies\ManufacturerPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManufacturerPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ManufacturerPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected Manufacturer $manufacturer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new ManufacturerPolicy();
        
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        
        $this->user = User::factory()->create(['business_id' => $business1->id]);
        $this->otherUser = User::factory()->create(['business_id' => $business2->id]);
        $this->manufacturer = Manufacturer::factory()->create(['business_id' => $business1->id]);
    }

    public function test_view_any_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_view_allows_owner(): void
    {
        $this->assertTrue($this->policy->view($this->user, $this->manufacturer));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->manufacturer));
    }

    public function test_create_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_allows_owner(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->manufacturer));
    }

    public function test_delete_allows_owner(): void
    {
        $this->assertTrue($this->policy->delete($this->user, $this->manufacturer));
    }

    public function test_delete_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->delete($this->otherUser, $this->manufacturer));
    }
}
