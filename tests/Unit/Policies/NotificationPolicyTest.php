<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\Notification;
use App\Models\User;
use App\Policies\NotificationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected User $adminUser;
    protected Notification $notification;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new NotificationPolicy();
        
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();
        
        $this->user = User::factory()->create(['business_id' => $business1->id, 'role' => 'user']);
        $this->otherUser = User::factory()->create(['business_id' => $business2->id, 'role' => 'user']);
        $this->adminUser = User::factory()->create(['business_id' => $business1->id, 'role' => 'admin']);
        $this->notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => User::class,
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
        $this->assertTrue($this->policy->view($this->user, $this->notification));
    }

    public function test_view_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->notification));
    }

    public function test_create_allows_admin(): void
    {
        $this->assertTrue($this->policy->create($this->adminUser));
    }

    public function test_create_denies_regular_user(): void
    {
        $this->assertFalse($this->policy->create($this->user));
    }

    public function test_update_allows_owner(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->notification));
    }

    public function test_update_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->update($this->otherUser, $this->notification));
    }

    public function test_delete_allows_owner(): void
    {
        $this->assertTrue($this->policy->delete($this->user, $this->notification));
    }

    public function test_delete_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->delete($this->otherUser, $this->notification));
    }

    public function test_mark_as_read_allows_owner(): void
    {
        $this->assertTrue($this->policy->markAsRead($this->user, $this->notification));
    }

    public function test_mark_as_read_denies_non_owner(): void
    {
        $this->assertFalse($this->policy->markAsRead($this->otherUser, $this->notification));
    }
}
