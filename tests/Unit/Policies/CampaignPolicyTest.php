<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Campaign;
use App\Models\User;
use App\Policies\CampaignPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CampaignPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected Campaign $campaign;
    protected Business $business;
    protected Business $otherBusiness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CampaignPolicy();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create(['business_category_id' => $category->id]);
        $this->otherBusiness = Business::factory()->create(['business_category_id' => $category->id]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->otherUser = User::factory()->create([
            'business_id' => $this->otherBusiness->id,
        ]);

        $this->campaign = Campaign::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'draft',
        ]);
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
        $this->assertTrue($this->policy->view($this->user, $this->campaign));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->campaign));
    }

    public function test_create_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_create_denies_users_without_business(): void
    {
        $userWithoutBusiness = User::factory()->create(['business_id' => null]);
        $this->assertFalse($this->policy->create($userWithoutBusiness));
    }

    public function test_update_allows_owner(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->campaign));
    }

    public function test_update_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->update($this->otherUser, $this->campaign));
    }

    public function test_delete_allows_owner_when_draft(): void
    {
        $this->assertTrue($this->policy->delete($this->user, $this->campaign));
    }

    public function test_delete_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->delete($this->otherUser, $this->campaign));
    }

    public function test_delete_denies_when_not_draft(): void
    {
        $campaign = Campaign::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'sent',
        ]);
        $this->assertFalse($this->policy->delete($this->user, $campaign));
    }

    public function test_send_allows_owner_when_draft(): void
    {
        $this->assertTrue($this->policy->send($this->user, $this->campaign));
    }

    public function test_send_allows_owner_when_scheduled(): void
    {
        $campaign = Campaign::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'scheduled',
        ]);
        $this->assertTrue($this->policy->send($this->user, $campaign));
    }

    public function test_send_denies_when_sent(): void
    {
        $campaign = Campaign::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'sent',
        ]);
        $this->assertFalse($this->policy->send($this->user, $campaign));
    }
}
