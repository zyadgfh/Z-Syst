<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Services\BranchLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_have_unlimited_branches(): void
    {
        $company = Company::factory()->create([
            'is_unlimited_branches' => true,
            'max_branches' => null,
        ]);

        $this->assertTrue($company->canCreateBranch());
        $this->assertNull($company->remaining_branches);
        $this->assertEquals(0, $company->branch_usage_percentage);
    }

    public function test_company_respects_branch_limit(): void
    {
        $company = Company::factory()->create([
            'max_branches' => 5,
            'is_unlimited_branches' => false,
        ]);

        $this->assertTrue($company->canCreateBranch());

        Branch::factory()->count(5)->create(['company_id' => $company->id]);

        $this->assertFalse($company->canCreateBranch());
        $this->assertEquals(0, $company->remaining_branches);
        $this->assertEquals(100, $company->branch_usage_percentage);
        $this->assertTrue($company->is_at_limit);
        $this->assertTrue($company->is_near_limit);
    }

    public function test_branch_limit_service_enforcement(): void
    {
        $company = Company::factory()->create([
            'max_branches' => 3,
            'is_unlimited_branches' => false,
        ]);

        $service = new BranchLimitService;

        $this->assertTrue($service->canCreateBranch($company));

        Branch::factory()->count(3)->create(['company_id' => $company->id]);

        $this->assertFalse($service->canCreateBranch($company));

        $this->expectException(\Exception::class);
        $service->enforceBeforeCreate($company);
    }

    public function test_decreasing_limit_does_not_delete_branches(): void
    {
        $company = Company::factory()->create([
            'max_branches' => 10,
            'is_unlimited_branches' => false,
        ]);

        Branch::factory()->count(5)->create(['company_id' => $company->id]);

        $company->update(['max_branches' => 3]);

        $this->assertEquals(3, $company->max_branches);
        $this->assertEquals(5, $company->current_branches_count);
        $this->assertTrue($company->is_at_limit);
    }

    public function test_branch_count_caching(): void
    {
        $company = Company::factory()->create([
            'max_branches' => 10,
            'is_unlimited_branches' => false,
        ]);

        $service = new BranchLimitService;

        $count1 = $service->getCurrentBranchCount($company);
        $count2 = $service->getCurrentBranchCount($company);

        $this->assertEquals($count1, $count2);

        Branch::factory()->create(['company_id' => $company->id]);

        $count3 = $service->getCurrentBranchCount($company);

        $this->assertEquals($count1 + 1, $count3);
    }
}
