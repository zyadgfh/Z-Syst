<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\FinancialAuditLog;
use App\Models\User;
use App\Policies\FinancialAuditLogPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAuditLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialAuditLogPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected Business $business;
    protected Business $otherBusiness;
    protected FinancialAuditLog $log;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new FinancialAuditLogPolicy();

        $this->business = Business::factory()->create();
        $this->otherBusiness = Business::factory()->create();

        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->otherUser = User::factory()->create(['business_id' => $this->otherBusiness->id]);

        $this->log = FinancialAuditLog::create([
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
        $noBusinessUser = User::factory()->create(['business_id' => null]);
        $this->assertFalse($this->policy->viewAny($noBusinessUser));
    }

    public function test_view_allows_owner_of_log(): void
    {
        $this->assertTrue($this->policy->view($this->user, $this->log));
    }

    public function test_view_denies_other_business(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->log));
    }

    public function test_create_allows_user_with_business(): void
    {
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_allows_same_business(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->log));
    }

    public function test_update_denies_other_business(): void
    {
        $this->assertFalse($this->policy->update($this->otherUser, $this->log));
    }

    public function test_delete_always_denied(): void
    {
        // Financial audit logs should never be deletable
        $this->assertFalse($this->policy->delete($this->user, $this->log));
    }
}
