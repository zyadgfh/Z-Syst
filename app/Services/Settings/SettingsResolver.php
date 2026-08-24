<?php

namespace App\Services\Settings;

use App\Models\SettingDefinition;
use App\Models\SettingValue;
use App\Models\User;
use App\Models\Branch;
use App\Models\Business;
use Illuminate\Support\Facades\Cache;

/**
 * Central settings resolver that resolves effective setting values
 * by walking the inheritance chain:
 *
 * USER → ROLE → BRANCH → ORGANIZATION → SYSTEM DEFAULT
 *
 * There must be one authoritative resolver for all setting resolution.
 */
class SettingsResolver
{
    /**
     * The priority order for resolving settings (highest to lowest).
     */
    private const SCOPE_PRIORITY = [
        'user',
        'role',
        'branch',
        'organization',
        'system',
    ];

    /**
     * Resolve the effective value of a setting for a given user.
     *
     * @param string $key The setting key (e.g., 'sales.autoPrint')
     * @param User|null $user The user context. If null, uses the authenticated user.
     * @return mixed The resolved value, or null if not found anywhere in the chain.
     */
    public function get(string $key, ?User $user = null): mixed
    {
        $user = $user ?? auth()->user();

        // Try to get from cache first
        $cacheKey = $this->buildCacheKey($key, $user);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached['value'];
        }

        // Resolve through the inheritance chain
        $result = $this->resolve($key, $user);

        // Cache the resolved value (short TTL to avoid stale data)
        if ($result !== null) {
            Cache::put($cacheKey, [
                'value' => $result['value'],
                'source' => $result['source'],
                'source_id' => $result['source_id'],
            ], now()->addMinutes(15));
        }

        return $result['value'] ?? null;
    }

    /**
     * Resolve a setting and return full resolution info (value, source, etc.).
     *
     * @return array{value: mixed, source: string, source_id: mixed}|null
     */
    public function resolveWithSource(string $key, ?User $user = null): ?array
    {
        $user = $user ?? auth()->user();
        $cacheKey = $this->buildCacheKey($key, $user);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->resolve($key, $user);

        if ($result !== null) {
            Cache::put($cacheKey, $result, now()->addMinutes(15));
        }

        return $result;
    }

    /**
     * Resolve a setting value for a specific scope type.
     *
     * Used by the UI to show what value a specific scope has.
     */
    public function getForScope(string $key, string $scopeType, $scopeId = null): mixed
    {
        $definition = SettingDefinition::where('key', $key)->first();
        if (!$definition) {
            return null;
        }

        $value = SettingValue::where('setting_definition_id', $definition->id)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->first();

        return $value?->value;
    }

    /**
     * Get the default value for a setting definition.
     */
    public function getDefault(string $key): mixed
    {
        $definition = SettingDefinition::where('key', $key)->first();

        return $definition?->default_value;
    }

    /**
     * Get multiple settings at once for a user.
     *
     * @param array $keys Setting keys to resolve
     * @param User|null $user
     * @return array<string, mixed>
     */
    public function getMany(array $keys, ?User $user = null): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $user);
        }

        return $results;
    }

    /**
     * Get all settings for a specific module, resolved for a user.
     *
     * @return array<string, mixed>
     */
    public function getModuleSettings(string $module, ?User $user = null): array
    {
        $definitions = SettingDefinition::where('module', $module)
            ->where('is_enabled', true)
            ->get();

        $results = [];
        foreach ($definitions as $definition) {
            $results[$definition->key] = $this->get($definition->key, $user);
        }

        return $results;
    }

    /**
     * Clear cache for a specific setting and user.
     */
    public function clearCache(string $key, ?User $user = null): void
    {
        $user = $user ?? auth()->user();
        $cacheKey = $this->buildCacheKey($key, $user);
        Cache::forget($cacheKey);
    }

    /**
     * Clear all settings cache for a user.
     */
    public function clearUserCache(?User $user = null): void
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return;
        }

        // Clear the user-specific cache
        $pattern = "setting:{$user->id}:*";
        Cache::forget("setting:{$user->id}");

        // Also clear scope-specific caches
        if ($user->business_id) {
            Cache::forget("settings:org:{$user->business_id}");
        }

        // Clear all role-based caches for the user's roles
        foreach ($user->roles as $role) {
            Cache::forget("settings:role:{$role->id}");
        }

        // Clear all branch-based caches for the user
        if ($user->branch_id) {
            Cache::forget("settings:branch:{$user->branch_id}");
        }
    }

    /**
     * Clear all settings cache globally.
     */
    public function clearAllCache(): void
    {
        // Use tag-based clearing if available
        try {
            Cache::tags(['settings'])->flush();
        } catch (\Exception $e) {
            // Fallback: clear common settings cache keys
            // This is not as precise but works across all cache drivers
            Cache::forget('settings_resolver');
        }
    }

    /**
     * Internal resolution logic.
     *
     * Walks through the inheritance chain from highest to lowest priority.
     */
    private function resolve(string $key, ?User $user): ?array
    {
        $definition = SettingDefinition::where('key', $key)->where('is_enabled', true)->first();
        if (!$definition) {
            return null;
        }

        // Walk through the scope priority order
        foreach (self::SCOPE_PRIORITY as $scopeType) {
            // Check if this scope type is allowed for this setting
            if (!$this->isScopeAllowed($definition, $scopeType)) {
                continue;
            }

            // Check if this user can actually have this scope
            if (!$this->userHasScope($user, $scopeType)) {
                continue;
            }

            $scopeId = $this->getScopeId($user, $scopeType);

            $value = SettingValue::where('setting_definition_id', $definition->id)
                ->where('scope_type', $scopeType)
                ->where('scope_id', $scopeId)
                ->first();

            if ($value) {
                return [
                    'value' => $definition->castValue($value->value),
                    'source' => $scopeType,
                    'source_id' => $value->scope_id,
                ];
            }
        }

        // Fallback to definition default
        return [
            'value' => $definition->default_value,
            'source' => 'default',
            'source_id' => null,
        ];
    }

    /**
     * Check if a scope type is allowed for a setting definition.
     */
    private function isScopeAllowed(SettingDefinition $definition, string $scopeType): bool
    {
        return match ($scopeType) {
            'system' => $definition->is_system_configurable,
            'organization' => $definition->is_organization_configurable,
            'branch' => $definition->is_branch_configurable,
            'role' => $definition->is_role_configurable,
            'user' => $definition->is_user_configurable,
            default => false,
        };
    }

    /**
     * Check if a user can have values at a specific scope level.
     */
    private function userHasScope(?User $user, string $scopeType): bool
    {
        if (!$user) {
            return $scopeType === 'system';
        }

        return match ($scopeType) {
            'system' => true,
            'organization' => (bool) $user->business_id,
            'branch' => (bool) ($user->branch_id ?? $user->business_id),
            'role' => $user->roles()->count() > 0,
            'user' => true,
            default => false,
        };
    }

    /**
     * Get the scope ID for a user at a specific scope level.
     */
    private function getScopeId(?User $user, string $scopeType): ?int
    {
        if (!$user) {
            return null;
        }

        return match ($scopeType) {
            'system' => null,
            'organization' => $user->business_id,
            'branch' => $user->branch_id ?? $user->business_id,
            'role' => $user->roles->first()?->id,
            'user' => $user->id,
            default => null,
        };
    }

    /**
     * Build a cache key for a setting and user.
     */
    private function buildCacheKey(string $key, ?User $user): string
    {
        $userId = $user?->id ?? 'guest';
        $businessId = $user?->business_id ?? 0;

        return "setting:{$userId}:{$businessId}:{$key}";
    }
}
