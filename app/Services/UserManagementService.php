<?php

namespace App\Services;

use App\Models\User;
use App\Support\Supabase\SupabaseAuthClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * User administration against Supabase.
 *
 * PATH B rewrote this service. The previous implementation assumed a MySQL
 * `users` table with email/password columns and the Spatie tables
 * (roles / permissions / model_has_roles), none of which exist in Supabase:
 *
 *   - identity            -> auth.users  (GoTrue)
 *   - profile + role      -> public.app_users
 *   - authorisation map   -> config/supabase.php
 *
 * Consequence: a staff member cannot be created purely from this application
 * unless a service-role key is configured (the GoTrue Admin API is the only
 * way to create an identity programmatically). When it is not configured we
 * fail loudly with instructions instead of writing a half-user.
 */
class UserManagementService
{
    public function __construct(
        protected SupabaseAuthClient $auth,
        protected ?string $serviceRoleKey = null,
    ) {
        $this->serviceRoleKey = $serviceRoleKey ?: (string) config('supabase.service_role_key');
    }

    /* ---------------------------------------------------------------------
     | Users
     | ------------------------------------------------------------------ */

    /**
     * Create an identity in GoTrue, then its profile row in app_users.
     *
     * $userData: name, email, password, phone?, image?, lang?, status?, business_id?, branch_id?
     */
    public function createUserWithRole(array $userData, string $roleName): User
    {
        $email = (string) ($userData['email'] ?? '');
        $password = (string) ($userData['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new RuntimeException('email and password are required to create a user.');
        }

        $authId = $this->createAuthUser($email, $password, $userData['name'] ?? null);

        return DB::transaction(function () use ($authId, $userData, $roleName) {
            $user = User::query()->find($authId);

            if (! $user) {
                // id is not mass-assignable, so forceFill it explicitly.
                $user = (new User())->forceFill(['id' => $authId]);
            }

            $user->forceFill([
                'name' => $userData['name'] ?? $email,
                'phone' => $userData['phone'] ?? null,
                'image' => $userData['image'] ?? null,
                'lang' => $userData['lang'] ?? 'ar',
                'role' => $roleName,
                'status' => $this->normaliseStatus($userData['status'] ?? 'active'),
                'business_id' => $userData['business_id'] ?? null,
                'branch_id' => $userData['branch_id'] ?? null,
                'visibility' => $userData['visibility'] ?? null,
            ])->save();

            return $user->fresh();
        });
    }

    /**
     * Update the profile only. E-mail and password belong to GoTrue.
     */
    public function updateUserWithRole(User $user, array $userData, string $roleName): User
    {
        return DB::transaction(function () use ($user, $userData, $roleName) {
            $user->fill([
                'name' => $userData['name'] ?? $user->name,
                'phone' => $userData['phone'] ?? $user->phone,
                'image' => $userData['image'] ?? $user->image,
                'lang' => $userData['lang'] ?? $user->lang,
                'role' => $roleName,
                'status' => $this->normaliseStatus($userData['status'] ?? $user->status),
                'visibility' => $userData['visibility'] ?? $user->visibility,
            ]);

            if (! empty($userData['password'])) {
                $this->updateAuthPassword((string) $user->getKey(), (string) $userData['password']);
            }

            $user->save();

            return $user->fresh();
        });
    }

    public function bulkDeleteUsers(array $userIds): int
    {
        // Never delete a superadmin, whatever the UI sends.
        $safe = User::query()
            ->whereIn('id', $userIds)
            ->where('role', '!=', 'superadmin')
            ->pluck('id')
            ->all();

        return $safe === [] ? 0 : User::query()->whereIn('id', $safe)->delete();
    }

    /**
     * $status accepts 1/0, 'active'/'inactive'.
     */
    public function bulkChangeUserStatus(array $userIds, int|string $status): int
    {
        return User::query()
            ->whereIn('id', $userIds)
            ->where('role', '!=', 'superadmin')
            ->update(['status' => $this->normaliseStatus($status), 'updated_at' => now()]);
    }

    /* ---------------------------------------------------------------------
     | Roles & permissions — config driven, read only
     | ------------------------------------------------------------------ */

    /**
     * @return array{total_roles:int, roles:array<int, array{name:string, users_count:int, permissions_count:int}>}
     */
    public function getRoleStatistics(): array
    {
        $counts = User::query()
            ->selectRaw('role, count(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $roles = [];

        foreach ((array) config('supabase.permissions', []) as $name => $permissions) {
            $roles[] = [
                'id' => $name,
                'name' => $name,
                'users_count' => (int) ($counts[$name] ?? 0),
                'permissions_count' => $permissions === ['*'] ? -1 : count($permissions),
            ];
        }

        return ['total_roles' => count($roles), 'roles' => $roles];
    }

    /**
     * @return array<string, array<int, array{id:string, name:string}>>
     */
    public function getPermissionsGrouped(): array
    {
        $permissions = [];

        foreach ((array) config('supabase.permissions', []) as $rolePermissions) {
            foreach ($rolePermissions as $permission) {
                if ($permission !== '*') {
                    $permissions[$permission] = true;
                }
            }
        }

        $grouped = [];

        foreach (array_keys($permissions) as $permission) {
            $grouped[$this->extractModuleFromPermission($permission)][] = [
                'id' => $permission,
                'name' => $permission,
            ];
        }

        ksort($grouped);

        return $grouped;
    }

    public function getUserStatistics(array $filters = []): array
    {
        $query = User::query();

        foreach (['role', 'status', 'business_id'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        $users = $query->get();

        return [
            'total_users' => $users->count(),
            'active_users' => $users->where('status', 'active')->count(),
            'inactive_users' => $users->where('status', '!=', 'active')->count(),
            'users_by_role' => $users->groupBy('role')->map->count()->toArray(),
            'users_by_business' => $users->whereNotNull('business_id')->groupBy('business_id')->map->count()->toArray(),
        ];
    }

    /**
     * Effective abilities come from config/supabase.php for the user's role.
     *
     * @return array{user_id:string, role:string, all_permissions:array<int,string>}
     */
    public function getUserEffectivePermissions(User $user): array
    {
        $map = (array) config('supabase.permissions', []);
        $permissions = $map[$user->role] ?? [];

        return [
            'user_id' => (string) $user->getKey(),
            'role' => (string) $user->role,
            'all_permissions' => $permissions === ['*'] ? ['*'] : array_values($permissions),
        ];
    }

    public function validateUserPermission(User $user, string $permission): bool
    {
        $permissions = $this->getUserEffectivePermissions($user)['all_permissions'];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    /**
     * Copying permissions between users has no meaning under Path B: a user's
     * abilities derive from their single role.
     */
    public function cloneUserPermissions(User $sourceUser, User $targetUser): User
    {
        if ($sourceUser->role === 'superadmin') {
            throw new RuntimeException('Cannot clone superadmin permissions');
        }

        $targetUser->fill(['role' => $sourceUser->role])->save();

        return $targetUser->fresh();
    }

    public function getUserActivity(User $user, int $limit = 50): array
    {
        return [
            'user_id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'business_id' => $user->business_id,
            'created_at' => $user->created_at,
        ];
    }

    /* ---------------------------------------------------------------------
     | Removed surface
     | ------------------------------------------------------------------ */

    public function createRoleWithPermissions(array $roleData, array $permissionIds): never
    {
        throw new RuntimeException(
            'Roles are fixed in config/supabase.php under Path B; there is no roles table in Supabase.'
        );
    }

    public function updateRolePermissions($role, array $permissionIds): never
    {
        throw new RuntimeException(
            'Roles are fixed in config/supabase.php under Path B; there is no roles table in Supabase.'
        );
    }

    public function createPermission(array $permissionData): never
    {
        throw new RuntimeException(
            'Permissions are fixed in config/supabase.php under Path B; there is no permissions table in Supabase.'
        );
    }

    public function assignPermissionsToRole($role, array $permissionIds): never
    {
        throw new RuntimeException(
            'Permissions are fixed in config/supabase.php under Path B; there is no roles table in Supabase.'
        );
    }

    /* ---------------------------------------------------------------------
     | Internals
     | ------------------------------------------------------------------ */

    protected function normaliseStatus(mixed $status): string
    {
        if (is_bool($status)) {
            return $status ? 'active' : 'inactive';
        }

        if (is_numeric($status)) {
            return ((int) $status) === 1 ? 'active' : 'inactive';
        }

        return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
    }

    protected function extractModuleFromPermission(string $permissionName): string
    {
        $parts = explode('.', $permissionName);

        return ucfirst($parts[0] ?? 'General');
    }

    /**
     * Create the GoTrue identity via the Admin API (requires service_role).
     */
    protected function createAuthUser(string $email, string $password, ?string $name): string
    {
        if ($this->serviceRoleKey === '') {
            throw new RuntimeException(
                'Cannot create a user: SUPABASE_SERVICE_ROLE_KEY is not configured. '.
                'Create the account in Supabase -> Authentication -> Users, then attach the profile here.'
            );
        }

        $response = Http::withHeaders([
            'apikey' => $this->serviceRoleKey,
            'Authorization' => 'Bearer '.$this->serviceRoleKey,
        ])->acceptJson()->asJson()->timeout(15)->post(
            rtrim((string) config('supabase.url'), '/').'/auth/v1/admin/users',
            [
                'email' => $email,
                'password' => $password,
                'email_confirm' => true,
                'user_metadata' => ['name' => $name],
            ]
        );

        $id = $response->json('id');

        if (! $response->successful() || ! is_string($id)) {
            throw new RuntimeException('GoTrue admin create failed: '.$response->body());
        }

        return $id;
    }

    /**
     * Verify a password by asking GoTrue for a token (no local hash exists).
     */
    public function verifyPassword(string $email, string $password): bool
    {
        try {
            $this->auth->signInWithPassword($email, $password);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Change a password through the GoTrue Admin API.
     * Returns false when SUPABASE_SERVICE_ROLE_KEY is not configured.
     */
    public function changePassword(User $user, string $password): bool
    {
        if ($this->serviceRoleKey === '') {
            return false;
        }

        return $this->updateAuthPassword((string) $user->getKey(), $password);
    }

    protected function updateAuthPassword(string $authId, string $password): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer '.$this->serviceRoleKey,
            ])->acceptJson()->asJson()->timeout(15)->put(
                rtrim((string) config('supabase.url'), '/').'/auth/v1/admin/users/'.$authId,
                ['password' => $password]
            );

            return $response->successful();
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
