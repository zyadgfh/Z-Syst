<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Policies\CompanyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchLimitApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_branch_limit_index(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $policy = new CompanyPolicy;

        $this->assertTrue($policy->viewAny($user));
    }

    public function test_non_super_admin_cannot_update_branch_limit(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();
        $policy = new CompanyPolicy;

        $this->assertFalse($policy->updateBranchLimit($user, $company));
    }
}
