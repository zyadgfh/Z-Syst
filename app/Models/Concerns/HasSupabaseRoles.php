<?php

namespace App\Models\Concerns;

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
        return (array) config('supabase.permissions.'.$this->roleName(), []);
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
        return collect($this->roles());
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
