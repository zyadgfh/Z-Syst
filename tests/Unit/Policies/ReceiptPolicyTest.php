<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\User;
use App\Policies\ReceiptPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ReceiptPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected Receipt $receipt;
    protected Business $business;
    protected Business $otherBusiness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ReceiptPolicy();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create(['business_category_id' => $category->id]);
        $this->otherBusiness = Business::factory()->create(['business_category_id' => $category->id]);

        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->otherUser = User::factory()->create(['business_id' => $this->otherBusiness->id]);

        $sale = Sale::factory()->create(['business_id' => $this->business->id]);
        $this->receipt = Receipt::factory()->create([
            'business_id' => $this->business->id,
            'sale_id' => $sale->id,
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
        $this->assertTrue($this->policy->view($this->user, $this->receipt));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->receipt));
    }

    public function test_generate_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->generate($this->user));
    }

    public function test_generate_denies_users_without_business(): void
    {
        $userWithoutBusiness = User::factory()->create(['business_id' => null]);
        $this->assertFalse($this->policy->generate($userWithoutBusiness));
    }
}
