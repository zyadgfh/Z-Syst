<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use App\Policies\SubscriptionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPolicy $policy;
    protected User $user;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new SubscriptionPolicy();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create(['business_category_id' => $category->id]);
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
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

    public function test_manage_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->manage($this->user));
    }

    public function test_manage_denies_users_without_business(): void
    {
        $userWithoutBusiness = User::factory()->create(['business_id' => null]);
        $this->assertFalse($this->policy->manage($userWithoutBusiness));
    }

    public function test_renew_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->renew($this->user));
    }

    public function test_renew_denies_users_without_business(): void
    {
        $userWithoutBusiness = User::factory()->create(['business_id' => null]);
        $this->assertFalse($this->policy->renew($userWithoutBusiness));
    }
}
