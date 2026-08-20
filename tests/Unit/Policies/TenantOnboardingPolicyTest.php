<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\OnboardingTemplate;
use App\Models\TenantOnboardingInstance;
use App\Models\User;
use App\Policies\TenantOnboardingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOnboardingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TenantOnboardingPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected User $adminUser;
    protected User $superadminUser;
    protected TenantOnboardingInstance $instance;
    protected OnboardingTemplate $template;
    protected Business $business;
    protected Business $otherBusiness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TenantOnboardingPolicy();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create(['business_category_id' => $category->id]);
        $this->otherBusiness = Business::factory()->create(['business_category_id' => $category->id]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'staff',
        ]);

        $this->otherUser = User::factory()->create([
            'business_id' => $this->otherBusiness->id,
            'role' => 'staff',
        ]);

        $this->adminUser = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'admin',
        ]);

        $this->superadminUser = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'superadmin',
        ]);

        $this->template = OnboardingTemplate::factory()->create([
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->instance = TenantOnboardingInstance::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'in_progress',
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
        $this->assertTrue($this->policy->view($this->user, $this->instance));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->instance));
    }

    public function test_start_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->start($this->user));
    }

    public function test_start_denies_users_without_business(): void
    {
        $userWithoutBusiness = User::factory()->create(['business_id' => null]);
        $this->assertFalse($this->policy->start($userWithoutBusiness));
    }

    public function test_process_step_allows_owner_when_in_progress(): void
    {
        $this->assertTrue($this->policy->processStep($this->user, $this->instance));
    }

    public function test_process_step_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->processStep($this->otherUser, $this->instance));
    }

    public function test_process_step_denies_when_completed(): void
    {
        $completedInstance = TenantOnboardingInstance::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'completed',
        ]);
        $this->assertFalse($this->policy->processStep($this->user, $completedInstance));
    }

    // Template tests
    public function test_view_any_templates_allows_users_with_business(): void
    {
        $this->assertTrue($this->policy->viewAnyTemplates($this->user));
    }

    public function test_view_template_allows_active(): void
    {
        $this->assertTrue($this->policy->viewTemplate($this->user, $this->template));
    }

    public function test_view_template_denies_inactive(): void
    {
        $inactiveTemplate = OnboardingTemplate::factory()->create(['is_active' => false]);
        $this->assertFalse($this->policy->viewTemplate($this->user, $inactiveTemplate));
    }

    public function test_create_template_allows_admin(): void
    {
        $this->assertTrue($this->policy->createTemplate($this->adminUser));
    }

    public function test_create_template_denies_staff(): void
    {
        $this->assertFalse($this->policy->createTemplate($this->user));
    }

    public function test_update_template_allows_admin(): void
    {
        $this->assertTrue($this->policy->updateTemplate($this->adminUser, $this->template));
    }

    public function test_update_template_denies_staff(): void
    {
        $this->assertFalse($this->policy->updateTemplate($this->user, $this->template));
    }

    public function test_delete_template_allows_superadmin(): void
    {
        $this->assertTrue($this->policy->deleteTemplate($this->superadminUser, $this->template));
    }

    public function test_delete_template_denies_admin(): void
    {
        $this->assertFalse($this->policy->deleteTemplate($this->adminUser, $this->template));
    }

    public function test_delete_template_denies_default(): void
    {
        $defaultTemplate = OnboardingTemplate::factory()->create(['is_default' => true]);
        $this->assertFalse($this->policy->deleteTemplate($this->superadminUser, $defaultTemplate));
    }
}
