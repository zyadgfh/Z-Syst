<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RbacService
{
    public function __construct(private int $cacheTtl = 3600) {}

    public function getUserPermissions(User $user): array
    {
        return Cache::remember("user:{$user->id}:permissions", $this->cacheTtl, function () use ($user) {
            return $user->getAllPermissions()->pluck('slug')->toArray();
        });
    }

    public function clearUserPermissionsCache(User $user): void
    {
        Cache::forget("user:{$user->id}:permissions");
    }

    public function assignRoleToUser(User $user, Role $role): void
    {
        DB::transaction(function () use ($user, $role) {
            $user->assignRole($role);
            $this->clearUserPermissionsCache($user);
        });
    }

    public function removeRoleFromUser(User $user, Role $role): void
    {
        DB::transaction(function () use ($user, $role) {
            $user->removeRole($role);
            $this->clearUserPermissionsCache($user);
        });
    }

    public function syncPermissionsForRole(Role $role, array $permissionSlugs): void
    {
        DB::transaction(function () use ($role, $permissionSlugs) {
            $permissions = Permission::whereIn('slug', $permissionSlugs)->get();
            $role->permissions()->sync($permissions->pluck('id'));
        });
    }

    public function clearAllPermissionsCache(): void
    {
        Cache::flush();
    }
}
