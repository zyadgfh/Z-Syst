<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();

        $user->assignRole($role1);
        $user->assignRole($role2);

        $this->assertTrue($user->hasRole($role1->slug));
        $this->assertTrue($user->hasRole($role2->slug));
        $this->assertEquals(2, $user->roles()->count());
    }

    public function test_role_permission_inheritance(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        $role->permissions()->attach($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasPermission($permission->slug));
    }

    public function test_permission_removal_clears_cache(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        $role->permissions()->attach($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasPermission($permission->slug));

        $role->permissions()->detach($permission);

        $this->assertFalse($user->hasPermission($permission->slug));
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $role = Role::factory()->system()->create();

        $this->assertTrue($role->is_system);
    }
}
