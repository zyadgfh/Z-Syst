<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\FinancialAuditLog;
use App\Models\User;
use App\Policies\FinancialAuditPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAuditPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialAuditPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected Business $business;
    protected FinancialAuditLog $audit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new FinancialAuditPolicy();

        $this->business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

        $this->audit = FinancialAuditLog::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'audit_number' => 'FA-00001',
            'audit_type' => 'monthly',
            'start_date' => now()->subDays(30),
            'end_date' => now(),
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
        $this->assertTrue($this->policy->view($this->user, $this->audit));
    }

    public function test_view_denies_other_business(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->audit));
    }

    public function test_create_allows_user_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_allows_same_business_when_pending(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->audit));
    }

    public function test_update_denies_when_completed(): void
    {
        $this->audit->update(['status' => 'completed']);
        $this->assertFalse($this->policy->update($this->user, $this->audit));
    }

    public function test_delete_allows_when_pending_same_business(): void
    {
        $this->assertTrue($this->policy->delete($this->user, $this->audit));
    }

    public function test_delete_denies_when_not_pending(): void
    {
        $this->audit->update(['status' => 'in_progress']);
        $this->assertFalse($this->policy->delete($this->user, $this->audit));
    }

    public function test_delete_denies_other_business(): void
    {
        $this->assertFalse($this->policy->delete($this->otherUser, $this->audit));
    }

    public function test_start_allows_when_pending(): void
    {
        $this->assertTrue($this->policy->start($this->user, $this->audit));
    }

    public function test_start_denies_when_not_pending(): void
    {
        $this->audit->update(['status' => 'in_progress']);
        $this->assertFalse($this->policy->start($this->user, $this->audit));
    }

    public function test_complete_allows_when_in_progress(): void
    {
        $this->audit->update(['status' => 'in_progress']);
        $this->assertTrue($this->policy->complete($this->user, $this->audit));
    }

    public function test_complete_denies_when_not_in_progress(): void
    {
        $this->assertFalse($this->policy->complete($this->user, $this->audit));
    }

    public function test_cancel_allows_when_pending(): void
    {
        $this->assertTrue($this->policy->cancel($this->user, $this->audit));
    }

    public function test_cancel_allows_when_in_progress(): void
    {
        $this->audit->update(['status' => 'in_progress']);
        $this->assertTrue($this->policy->cancel($this->user, $this->audit));
    }

    public function test_cancel_denies_when_completed(): void
    {
        $this->audit->update(['status' => 'completed']);
        $this->assertFalse($this->policy->cancel($this->user, $this->audit));
    }

    public function test_view_report_allows_when_completed(): void
    {
        $this->audit->update(['status' => 'completed']);
        $this->assertTrue($this->policy->viewReport($this->user, $this->audit));
    }

    public function test_view_report_denies_when_not_completed(): void
    {
        $this->assertFalse($this->policy->viewReport($this->user, $this->audit));
    }
}
