<?php

namespace App\Models\Concerns;

use App\Models\UserPermission;
use BackedEnum;

/**
 * Spatie-compatible role helpers backed by the single app_users.role column.
 *
 * The Supabase schema has no roles / permissions / model_has_roles tables:
 * authorisation is one text column plus the map in config/supabase.php.
 * Existing Blade views calling $user->hasRole(...) keep working unchanged.
 */
trait HasSupabaseRoles
{
    public function roleName(): ?string
    {
        return $this->role ?? null;
    }

    public function roles(): array
    {
        $role = $this->roleName();

        return $role ? [$role] : [];
    }

    public function hasRole(string|array $roles): bool
    {
        foreach ((array) $roles as $role) {
            if ($this->roleName() === $role) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function abilities(): array
    {
        return array_values(array_unique(array_merge(
            (array) config('supabase.permissions.'.$this->roleName(), []),
            $this->grantedPermissions(),
        )));
    }

    /**
     * Runtime permission grants (user_permissions table) — written by
     * givePermissionTo() on top of the static role map.
     */
    public function grantedPermissions(): array
    {
        static $cache = [];

        $key = $this->getKey();
        if (! array_key_exists($key, $cache)) {
            $cache[$key] = ($key !== null)
                ? UserPermission::query()->where('user_id', $key)->pluck('name')->all()
                : [];
        }

        return $cache[$key];
    }

    /** Spatie-compatible: the role column IS the single role store. */
    public function assignRole(...$roles): static
    {
        $name = collect($roles)->flatten()->filter()->first();
        if (is_string($name) && filled($name)) {
            $this->forceFill(['role' => $name])->save();
        }

        return $this;
    }

    public function syncRoles(...$roles): static
    {
        return $this->assignRole(...$roles);
    }

    /** Spatie-compatible API: grant extra abilities beyond the role map. */
    public function givePermissionTo(...$permissions): static
    {
        $names = collect($permissions)
            ->flatten()
            ->map(fn ($p) => $p instanceof BackedEnum ? $p->value : (string) $p)
            ->filter()
            ->unique()
            ->all();

        foreach ($names as $name) {
            UserPermission::firstOrCreate(['user_id' => $this->getKey(), 'name' => $name]);
        }

        return $this;
    }

    public function revokePermissionTo(...$permissions): static
    {
        $names = collect($permissions)->flatten()->map(fn ($p) => (string) $p)->all();

        UserPermission::query()->where('user_id', $this->getKey())->whereIn('name', $names)->delete();

        return $this;
    }

    public function hasPermission(string $ability): bool
    {
        $abilities = $this->abilities();

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /** Spatie-compatible alias (used by existing controllers). */
    public function hasPermissionTo(string $permission): bool
    {
        return $this->hasPermission($permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Spatie's PermissionRegistrar Gate::before hook calls exactly this
     * method (method_exists check) to let a user bypass gate abilities.
     * Without it every spatie permission:* middleware denies users whose
     * abilities come from config/supabase.php.
     */
    public function checkPermissionTo(string $permission, $guard = null): bool
    {
        return $this->hasPermission($permission);
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    public function getRoleNames(): array
    {
        return $this->roles();
    }

    public function getAllPermissions(): array
    {
        return $this->abilities();
    }

    /**
     * Kept for call sites written against Spatie's API.
     */
    public function can($ability, $arguments = []): bool
    {
        return $this->hasPermission((string) $ability);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['admin', 'superadmin']);
    }
}
