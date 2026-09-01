<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserManagementService
{
    /**
     * Create user with role
     */
    public function createUserWithRole(array $userData, string $roleName): User
    {
        return DB::transaction(function () use ($userData, $roleName) {
            $role = Role::where('name', $roleName)->firstOrFail();

            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'] ?? null,
                'image' => $userData['image'] ?? null,
                'password' => Hash::make($userData['password']),
                'business_id' => $userData['business_id'] ?? null,
                'lang' => $userData['lang'] ?? 'en',
                'status' => $userData['status'] ?? 1,
            ]);

            $user->roles()->sync($role->id);

            return $user;
        });
    }

    /**
     * Update user with role
     */
    public function updateUserWithRole(User $user, array $userData, string $roleName): User
    {
        return DB::transaction(function () use ($user, $userData, $roleName) {
            $role = Role::where('name', $roleName)->firstOrFail();

            $user->update([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'] ?? null,
                'image' => $userData['image'] ?? $user->image,
                'password' => $userData['password'] ? Hash::make($userData['password']) : $user->password,
                'status' => $userData['status'] ?? $user->status,
            ]);

            $user->roles()->sync($role->id);

            return $user->fresh();
        });
    }

    /**
     * Create custom role with permissions
     */
    public function createRoleWithPermissions(array $roleData, array $permissionIds): Role
    {
        return DB::transaction(function () use ($roleData, $permissionIds) {
            $role = Role::create([
                'name' => $roleData['name'],
                'guard_name' => $roleData['guard_name'] ?? 'web',
            ]);

            $role->permissions()->sync($permissionIds);

            return $role;
        });
    }

    /**
     * Update role permissions
     */
    public function updateRolePermissions(Role $role, array $permissionIds): Role
    {
        $role->permissions()->sync($permissionIds);

        return $role->fresh();
    }

    /**
     * Create custom permission
     */
    public function createPermission(array $permissionData): Permission
    {
        return Permission::create([
            'name' => $permissionData['name'],
            'guard_name' => $permissionData['guard_name'] ?? 'web',
        ]);
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissionsToRole(Role $role, array $permissionIds): Role
    {
        $role->permissions()->sync($permissionIds);

        return $role->fresh();
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(array $filters = []): array
    {
        $query = User::query();

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        $users = $query->get();

        return [
            'total_users' => $users->count(),
            'active_users' => $users->where('status', 1)->count(),
            'inactive_users' => $users->where('status', 0)->count(),
            'users_by_role' => $users->groupBy('role')->map->count(),
            'users_by_business' => $users->whereNotNull('business_id')->groupBy('business_id')->map->count(),
        ];
    }

    /**
     * Get role statistics
     */
    public function getRoleStatistics(): array
    {
        $roles = Role::withCount('users')->get();

        return [
            'total_roles' => $roles->count(),
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                    'permissions_count' => $role->permissions->count(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get all permissions grouped by module
     */
    public function getPermissionsGrouped(): array
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $permission) {
            $module = $this->extractModuleFromPermission($permission->name);
            $grouped[$module][] = [
                'id' => $permission->id,
                'name' => $permission->name,
            ];
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * Extract module name from permission name
     */
    protected function extractModuleFromPermission(string $permissionName): string
    {
        // Remove action suffixes like -create, -read, -update, -delete
        $cleanName = preg_replace('/(-create|-read|-update|-delete)$/', '', $permissionName);

        // Extract the module part (first word before -)
        $parts = explode('-', $cleanName);

        return ucfirst($parts[0] ?? 'General');
    }

    /**
     * Bulk delete users
     */
    public function bulkDeleteUsers(array $userIds): int
    {
        // Prevent deleting superadmin
        $safeIds = User::whereIn('id', $userIds)
            ->where('role', '!=', 'superadmin')
            ->pluck('id');

        return User::whereIn('id', $safeIds)->delete();
    }

    /**
     * Bulk change user status
     */
    public function bulkChangeUserStatus(array $userIds, int $status): int
    {
        return User::whereIn('id', $userIds)
            ->where('role', '!=', 'superadmin')
            ->update(['status' => $status]);
    }

    /**
     * Clone user permissions from template user
     */
    public function cloneUserPermissions(User $sourceUser, User $targetUser): User
    {
        if ($sourceUser->role === 'superadmin') {
            throw new \Exception('Cannot clone superadmin permissions');
        }

        $targetUser->roles()->sync($sourceUser->roles->pluck('id'));
        $targetUser->permissions()->sync($sourceUser->permissions->pluck('id'));

        return $targetUser->fresh();
    }

    /**
     * Get user activity log
     */
    public function getUserActivity(User $user, int $limit = 50): array
    {
        // This would typically query an activity log table
        // For now, return basic user info
        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'business_id' => $user->business_id,
            'last_login' => $user->last_login_at ?? null,
            'created_at' => $user->created_at,
        ];
    }

    /**
     * Validate user permissions for specific action
     */
    public function validateUserPermission(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }

    /**
     * Get user's effective permissions (direct + through roles)
     */
    public function getUserEffectivePermissions(User $user): array
    {
        $directPermissions = $user->permissions->pluck('name')->toArray();
        $rolePermissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('name');
        })->toArray();

        $allPermissions = array_unique(array_merge($directPermissions, $rolePermissions));

        return [
            'user_id' => $user->id,
            'direct_permissions' => $directPermissions,
            'role_permissions' => $rolePermissions,
            'all_permissions' => $allPermissions,
        ];
    }
}
