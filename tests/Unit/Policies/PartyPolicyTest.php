<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Party;
use App\Models\User;
use App\Policies\PartyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected PartyPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected Party $party;
    protected Business $business;
    protected Business $otherBusiness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PartyPolicy();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create(['business_category_id' => $category->id]);
        $this->otherBusiness = Business::factory()->create(['business_category_id' => $category->id]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->otherUser = User::factory()->create([
            'business_id' => $this->otherBusiness->id,
        ]);

        $this->party = Party::factory()->create([
            'business_id' => $this->business->id,
        ]);
    }

    public function test_view_any_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_view_allows_owner(): void
    {
        $this->assertTrue($this->policy->view($this->user, $this->party));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->party));
    }

    public function test_create_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_allows_owner(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->party));
    }

    public function test_update_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->update($this->otherUser, $this->party));
    }

    public function test_delete_allows_owner(): void
    {
        $this->assertTrue($this->policy->delete($this->user, $this->party));
    }

    public function test_delete_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->delete($this->otherUser, $this->party));
    }
}
